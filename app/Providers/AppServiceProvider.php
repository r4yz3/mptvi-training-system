<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);

        // Force HTTPS URL generation in production (TLS terminates at Cloudflare/nginx).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Admin holds '*' — grant every ability (mirrors the demo's allow() with PERMS['admin']=['*']).
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        // Merge admin-saved overrides (requirements / reference lists / academic) over the file configs.
        try {
            if (Schema::hasTable('settings')) {
                Setting::applyConfigOverrides();
            }
        } catch (\Throwable $e) {
            // During early migrations the table may not exist yet — ignore.
        }
    }
}
