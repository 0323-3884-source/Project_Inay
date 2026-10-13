<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    protected $fillable = [
        'username',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function canAuthenticate(): bool
    {
        return in_array($this->role, ['admin', 'admin_dswd', 'admin_cho'], true);
    }

    public function dashboardRoute(): string
    {
        return match ($this->role) {
            'admin_dswd' => 'admin.dswd.dashboard',
            'admin_cho' => 'admin.cho.dashboard',
            default => 'admin.statistics',
        };
    }

    public function allowsRoute(?string $route): bool
    {
        if ($this->role === 'admin') return true;
        $shared = ['login', 'login.store', 'home', 'logout', 'admin.login', 'admin.login.store', 'admin.logout', 'admin.dashboard', 'notifications.*'];
        $patterns = match ($this->role) {
            'admin_dswd' => ['admin.dswd.*', 'admin.dswd-staff.*'],
            'admin_cho' => ['admin.cho.*', 'admin.program-staff.*', 'admin.educational-content.*', 'admin.maternal-vital-thresholds.*', 'admin.staff-messages.*', 'admin-staff-messages.*'],
            default => [],
        };
        return $route !== null && \Illuminate\Support\Str::is([...$shared, ...$patterns], $route);
    }
}
