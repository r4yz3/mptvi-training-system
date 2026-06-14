<?php

namespace App\Providers;

use App\Models\SecurityEvent;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
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

        // Security audit log — capture authentication events (Settings → Security).
        Event::listen(Login::class, fn (Login $e) => SecurityEvent::record('login_success', $e->user instanceof User ? $e->user : null));
        Event::listen(Logout::class, function (Logout $e) {
            // Skip the transient logout used to hand a user off to the 2FA challenge.
            if (session()->has('login.2fa')) {
                return;
            }
            SecurityEvent::record('logout', $e->user instanceof User ? $e->user : null);
        });
        Event::listen(Failed::class, fn (Failed $e) => SecurityEvent::record('login_failed', $e->user instanceof User ? $e->user : null, $e->credentials['email'] ?? null));
        Event::listen(Lockout::class, fn (Lockout $e) => SecurityEvent::record('lockout', null, $e->request->input('email')));

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
