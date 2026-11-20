<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('backups:run-due', function (\App\Support\BackupSchedule $schedule, \App\Support\DatabaseBackup $backup) {
    $result = $schedule->runDue($backup);
    $this->info('Automatic database backup: '.$result);
    return $result === 'failed' ? 1 : 0;
})->purpose('Create a private database backup when the administrator schedule is due');

\Illuminate\Support\Facades\Schedule::command('backups:run-due')->everyMinute();

Artisan::command('backups:check', function () {
    $connection = \Illuminate\Support\Facades\DB::connection();
    $this->info('Database driver: '.$connection->getDriverName());
    $excluded = array_filter(array_merge(config('backups.excluded_tables'), [config('session.table'), config('cache.stores.database.table'), config('cache.stores.database.lock_table')]));
    $rows = [];
    $database = $connection->getDriverName() === 'sqlite' ? 'main' : $connection->getDatabaseName();
    foreach ($connection->getSchemaBuilder()->getTables($database) as $table) {
        if (in_array($table['name'], $excluded, true) || str_starts_with($table['name'], 'sqlite_')) continue;
        $rows[] = [$table['name'], $table['engine'] ?? 'not reported'];
    }
    $this->table(['Application table', 'Storage engine'], $rows);
})->purpose('Read-only backup storage-engine diagnostics without exposing records or credentials');

Artisan::command('admin:restore-general {--apply : Restore the configured general admin account} {--force : Permit an explicitly approved production or remote database update}', function () {
    $connection = \Illuminate\Support\Facades\DB::connection();
    $local = $connection->getDriverName() === 'sqlite'
        || in_array($connection->getConfig('host'), ['localhost', '127.0.0.1', '::1'], true);
    if ($this->option('apply') && ! $this->option('force') && (app()->environment('production') || ! $local)) {
        $this->error('Production or remote restoration requires an explicit --force operation. No account changed.');
        return 1;
    }
    $admin = \App\Models\AdminUser::where('username', config('admin.default_username', 'admin'))->first();
    if (! $admin) {
        $this->error('The original general administrator account was not found. No account created.');
        return 1;
    }
    if ($admin->role === 'admin') {
        $this->info('The general administrator is already enabled. No account changed.');
        return 0;
    }
    if ($admin->role !== 'admin_retired') {
        $this->error('The configured account belongs to another role. No account changed.');
        return 1;
    }
    if (! $this->option('apply')) {
        $this->info('The general administrator is retired. Run with --apply to restore its original role and preserve its password.');
        return 0;
    }
    \App\Models\AdminUser::whereKey($admin->id)->where('role', 'admin_retired')->update(['role' => 'admin']);
    \Illuminate\Support\Facades\Log::channel('security')->info('general_admin_restored', ['admin_id' => $admin->id, 'source' => 'artisan']);
    $this->info('The general administrator is restored. Its password and department accounts were preserved.');
    return 0;
})->purpose('Restore only the original retired general administrator without resetting credentials');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dswd:create-staff {email} {name} {password} {--office=}', function (string $email, string $name, string $password) {
    if (! \Illuminate\Support\Facades\Schema::hasTable('dswd_staff')) {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    }

    $staff = \App\Models\DswdStaff::updateOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'password' => $password,
            'office' => $this->option('office'),
            'is_active' => true,
        ]
    );

    $this->info("DSWD staff account saved: {$staff->email} ({$staff->name})");
})->purpose('Create or update a DSWD / 4Ps Staff account');

Artisan::command('dswd:list-staff', function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('dswd_staff')) {
        $this->warn('dswd_staff table does not exist. Run migrations first.');
        return;
    }

    $accounts = \App\Models\DswdStaff::all(['id', 'name', 'email', 'office', 'is_active', 'created_at']);
    if ($accounts->isEmpty()) {
        $this->info('No DSWD staff accounts found.');
        return;
    }

    $this->table(['ID', 'Name', 'Email', 'Office', 'Active', 'Created At'], $accounts->toArray());
})->purpose('List all DSWD / 4Ps Staff accounts');
