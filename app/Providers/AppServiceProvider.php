<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::define('manage-database-backups', fn ($actor) => $actor instanceof \App\Models\AdminUser && $actor->canManageDatabaseBackups());
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Mother::class, \App\Policies\MotherPolicy::class);
        \Illuminate\Support\Facades\RateLimiter::for('login', function (\Illuminate\Http\Request $request) {
            $identifier = \Illuminate\Support\Str::lower(trim((string) $request->input('email', $request->input('username', ''))));

            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by('login-ip:'.$request->ip()),
                \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('login-account:'.hash('sha256', $identifier)),
            ];
        });
        Paginator::defaultView('pagination.portal');
        Paginator::defaultSimpleView('pagination.portal');
    }
}
