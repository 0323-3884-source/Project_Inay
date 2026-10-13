<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DepartmentAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (['admindswd' => 'admin_dswd', 'admincho' => 'admin_cho'] as $username => $role) {
                $existing = AdminUser::whereRaw('LOWER(username) = ?', [$username])->first();
                if ($existing) {
                    if ($existing->role !== $role) throw new \RuntimeException("Username {$username} already belongs to another administrator role; no account was overwritten.");
                    continue; // Preserve changed passwords and existing accounts on reruns.
                }
                AdminUser::create(['username' => $username, 'role' => $role, 'password' => Hash::make('ProjectInay2026')]);
            }
            // Retain the old account's ID and history, but remove its login access.
            AdminUser::where('username', config('admin.default_username', 'admin'))
                ->where('role', 'admin')->update(['role' => 'admin_retired']);
        });
    }
}
