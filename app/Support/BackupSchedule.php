<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\{Cache, File, Log, Storage};

class BackupSchedule
{
    private const PATH = 'database-backup-schedule.json';

    public function status(): array
    {
        $disk = Storage::disk('local');
        return ($disk->exists(self::PATH) ? json_decode($disk->get(self::PATH), true, 512, JSON_THROW_ON_ERROR) : []) + [
            'frequency' => 'disabled', 'next_run_at' => null, 'last_success_at' => null,
            'last_backup' => null, 'last_error_at' => null, 'retry_at' => null, 'checked_at' => null,
        ];
    }

    private function save(array $state): void
    {
        $path = Storage::disk('local')->path(self::PATH);
        File::ensureDirectoryExists(dirname($path));
        // Atomic replacement prevents readers from seeing partially written JSON.
        File::replace($path, json_encode($state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), 0600);
    }

    private function next(string $frequency): string
    {
        $now = CarbonImmutable::now('Asia/Manila');
        return ($frequency === 'weekly' ? $now->addWeek() : $now->addMonthNoOverflow())->toIso8601String();
    }

    public function configure(string $frequency, int $adminId): void
    {
        if (! in_array($frequency, ['disabled', 'weekly', 'monthly'], true)) {
            throw new \InvalidArgumentException('Invalid backup frequency.');
        }
        Cache::lock('database-backup-operation', 300)->get(function () use ($frequency, $adminId) {
            $state = $this->status();
            if ($state['frequency'] !== $frequency) {
                $state['next_run_at'] = $frequency === 'disabled' ? null : $this->next($frequency);
                $state['retry_at'] = null;
                $state['last_error_at'] = null;
            }
            $state['frequency'] = $frequency;
            $this->save($state);
            Log::channel('security')->info('database_backup_schedule_updated', ['admin_id' => $adminId, 'frequency' => $frequency]);
            return true;
        }) || throw new \RuntimeException('Another backup or recovery is running. Try again later.');
    }

    public function runDue(DatabaseBackup $backup): string
    {
        $lock = Cache::lock('database-backup-operation', 300);
        if (! $lock->get()) return 'busy';
        try {
            $state = $this->status();
            $state['checked_at'] = now()->toIso8601String();
            $this->save($state);
            if ($state['frequency'] === 'disabled' || ! $state['next_run_at']
                || CarbonImmutable::parse($state['next_run_at'])->isFuture()
                || ($state['retry_at'] && CarbonImmutable::parse($state['retry_at'])->isFuture())) return 'not-due';

            try {
                $path = $backup->create();
            } catch (\Throwable $exception) {
                $state['last_error_at'] = now()->toIso8601String();
                $state['retry_at'] = now()->addHour()->toIso8601String();
                $this->save($state);
                Log::channel('security')->error('scheduled_database_backup_failed', ['exception_type' => get_class($exception)]);
                return 'failed';
            }
            $state['last_success_at'] = now()->toIso8601String();
            $state['last_backup'] = basename($path);
            $state['next_run_at'] = $this->next($state['frequency']);
            $state['last_error_at'] = $state['retry_at'] = null;
            $this->save($state);
            Log::channel('security')->info('scheduled_database_backup_created', ['backup' => basename($path), 'frequency' => $state['frequency']]);
            return 'created';
        } finally {
            $lock->release();
        }
    }
}
