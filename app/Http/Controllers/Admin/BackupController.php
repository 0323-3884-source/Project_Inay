<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Support\DatabaseBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Log, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BackupController extends Controller
{
    private function admin(Request $request): AdminUser
    {
        $admin = $request->attributes->get('department_admin');
        abort_unless($admin instanceof AdminUser && $admin->canAuthenticate(), 403);
        \Illuminate\Support\Facades\Gate::forUser($admin)->authorize('manage-database-backups');
        return $admin;
    }

    private function audit(string $event, AdminUser $admin, array $details = []): void
    {
        Log::channel('security')->info($event, ['admin_id' => $admin->id, 'admin_role' => $admin->role] + $details);
    }

    public function index(Request $request, DatabaseBackup $backup)
    {
        $this->admin($request);
        $files = collect(Storage::disk('local')->files('database-backups'))->filter(fn ($path) => str_ends_with($path, '.sql'))->sortDesc()->take(20)->map(fn ($path) => basename($path));
        $preview = $request->session()->get('backup_preview');
        if ($preview && $preview['expires'] < now()->timestamp) {
            Storage::disk('local')->delete($preview['path']);
            $request->session()->forget('backup_preview');
            $preview = null;
        }
        return view('admin.backups', ['files' => $files, 'supported' => $backup->supported(), 'preview' => $preview, 'schedule' => app(\App\Support\BackupSchedule::class)->status()]);
    }

    public function schedule(Request $request, \App\Support\BackupSchedule $schedule)
    {
        $admin = $this->admin($request);
        $data = $request->validate(['frequency' => ['required', 'in:disabled,weekly,monthly']]);
        try {
            $schedule->configure($data['frequency'], $admin->id);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['schedule' => 'Schedule could not be saved. Another operation may be running; try again later.']);
        }
        return redirect()->route('admin.backups.index')->with('status', 'Automatic backup schedule saved for all administrators.');
    }

    public function create(Request $request, DatabaseBackup $backup)
    {
        $admin = $this->admin($request);
        $lock = Cache::lock('database-backup-operation', 300);
        abort_unless($lock->get(), 423, 'Another backup or recovery is running. Try again later.');
        try {
            $path = $backup->create();
            $this->audit('database_backup_created', $admin, ['backup' => basename($path)]);
            return Storage::disk('local')->download($path, basename($path), ['Content-Type' => 'application/sql', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
        } catch (ValidationException $exception) { throw $exception;
        } catch (\Throwable $exception) {
            $this->audit('database_backup_failed', $admin, ['exception_type' => get_class($exception)]);
            throw ValidationException::withMessages(['backup' => 'Backup could not be completed. No records were changed.']);
        } finally { $lock->release(); }
    }

    public function download(Request $request, string $file)
    {
        $admin = $this->admin($request);
        abort_unless(preg_match('/^[0-9]{8}-[0-9]{6}-[a-f0-9-]{36}\.sql$/D', $file), 404);
        $path = 'database-backups/'.$file;
        abort_unless(Storage::disk('local')->exists($path), 404);
        $this->audit('database_backup_downloaded', $admin, ['backup' => $file]);
        return Storage::disk('local')->download($path, $file, ['Content-Type' => 'application/sql', 'Cache-Control' => 'private, no-store']);
    }

    public function preview(Request $request, DatabaseBackup $backup)
    {
        $admin = $this->admin($request);
        $request->validate(['backup' => ['required', 'file', 'extensions:sql', 'max:20480']]);
        $contents = file_get_contents($request->file('backup')->getRealPath());
        $verified = $backup->inspect($contents);
        $old = $request->session()->get('backup_preview');
        if ($old) Storage::disk('local')->delete($old['path']);
        $path = 'database-backup-staging/'.Str::uuid().'.sql';
        abort_unless(Storage::disk('local')->put($path, $contents), 503);
        $request->session()->put('backup_preview', [
            'admin_id' => $admin->id, 'token' => Str::random(64), 'path' => $path,
            'expires' => now()->addMinutes(15)->timestamp, 'created_at' => $verified['metadata']['created_at'],
            'tables' => count($verified['metadata']['tables']), 'driver' => $verified['metadata']['driver'],
        ]);
        $this->audit('database_recovery_previewed', $admin);
        return redirect()->route('admin.backups.index');
    }

    public function restore(Request $request, DatabaseBackup $backup)
    {
        $admin = $this->admin($request);
        if (config('session.driver') !== 'database' && ! (app()->environment('testing') && config('session.driver') === 'array')) {
            throw ValidationException::withMessages(['backup' => 'Recovery requires database-backed sessions so all previous logins can be revoked safely.']);
        }
        $request->validate(['confirmation' => ['required', 'in:RESTORE ALL DATA'], 'maintenance_confirmed' => ['accepted'], 'token' => ['required', 'string', 'size:64']]);
        $preview = $request->session()->get('backup_preview');
        abort_unless($preview && $preview['admin_id'] === $admin->id && $preview['expires'] >= now()->timestamp && hash_equals($preview['token'], $request->input('token')), 403);
        abort_unless(Storage::disk('local')->exists($preview['path']), 404);
        $verified = $backup->inspect(Storage::disk('local')->get($preview['path']));
        $lock = Cache::lock('database-backup-operation', 300);
        abort_unless($lock->get(), 423, 'Another backup or recovery is running.');
        $safety = null;
        $completed = false;
        try {
            Cache::put('database-recovery-in-progress', true, now()->addMinutes(5));
            // Recovery cannot begin unless a fresh private safety copy was saved.
            $safety = $backup->create();
            $backup->restore($verified, function () {
                Cache::forever('database-security-epoch', (string) Str::uuid());
                if (config('session.driver') === 'database') {
                    \Illuminate\Support\Facades\DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->delete();
                }
            });
            $completed = true;
            $this->audit('database_recovery_completed', $admin, ['safety_backup' => basename($safety)]);
            Storage::disk('local')->delete($preview['path']);
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')->with('status', 'Database records restored. Sign in using an administrator account from the backup. A pre-restore safety backup was saved privately.');
        } catch (\Throwable $exception) {
            $this->audit('database_recovery_failed', $admin, ['exception_type' => get_class($exception), 'safety_backup' => $safety ? basename($safety) : null]);
            throw ValidationException::withMessages(['backup' => $completed
                ? 'Records were restored, but final cleanup failed. Sign out and inspect the security log before retrying.'
                : 'Recovery failed and its database transaction was rolled back. Check the private safety backup and security log.']);
        } finally {
            Cache::forget('database-recovery-in-progress');
            $lock->release();
        }
    }
}
