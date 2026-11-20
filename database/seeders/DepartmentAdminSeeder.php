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
            // Department consoles coexist with the general administrator.
            // Never disable the general account when seeding department users.
        });
    }
}
