<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $username = (string) config('admin.default_username', 'admin');
        $passwordHash = (string) config('admin.default_password_hash');
        $plainPassword = env('ADMIN_DEFAULT_PASSWORD');

        AdminUser::firstOrCreate(
            ['username' => $username],
            [
                'password' => $plainPassword
                    ? Hash::make((string) $plainPassword)
                    : $passwordHash,
            ]
        );
    }
}
