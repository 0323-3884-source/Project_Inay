<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
