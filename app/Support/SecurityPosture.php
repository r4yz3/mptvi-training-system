<?php

namespace App\Support;

use App\Models\SecurityEvent;
use App\Models\User;

/**
 * Security posture checklist for Settings → Security: turns the app's current
 * configuration + recent activity into a plain-English pass/warn report with
 * advice, so an admin can see at a glance how locked-down the system is.
 */
class SecurityPosture
{
    /** Seeded demo logins that must be removed before go-live. */
    private const DEMO_EMAILS = [
        'admin@mptvi.test', 'secretary@mptvi.test', 'registrar@mptvi.test',
        'cashier@mptvi.test', 'coordinator@mptvi.test',
    ];

    public function report(): array
    {
        $checks = $this->checks();

        return [
            'overall' => $this->overall($checks),
            'checks' => $checks,
            'twofactor' => $this->twoFactor(),
            'failed_24h' => SecurityEvent::whereIn('type', ['login_failed', 'lockout'])
                ->where('created_at', '>=', now()->subDay())->count(),
        ];
    }

    private function checks(): array
    {
        $prod = app()->environment('production');
        $out = [];

        // HTTPS / transport
        if ($prod) {
            $out[] = $this->check('https', 'HTTPS enforced', 'ok', 'Forced in production');
        } else {
            $out[] = $this->check('https', 'HTTPS', 'info', 'Local/LAN over HTTP — fine on a trusted network');
        }

        // Debug mode
        $debug = (bool) config('app.debug');
        $out[] = $this->check('debug', 'Debug mode', $debug && $prod ? 'warn' : 'ok', $debug ? 'On' : 'Off',
            $debug && $prod ? 'Turn APP_DEBUG off in production — error pages can leak details.' : null);

        // App key
        $key = (string) config('app.key') !== '';
        $out[] = $this->check('appkey', 'Encryption key', $key ? 'ok' : 'fail', $key ? 'Set' : 'Missing',
            $key ? null : 'Run php artisan key:generate — encryption and 2FA secrets depend on it.');

        // Secure session cookie (production)
        $secure = (bool) config('session.secure');
        $out[] = $this->check('cookie', 'Secure session cookie', $prod && ! $secure ? 'warn' : 'ok',
            $secure ? 'On' : ($prod ? 'Off' : 'Off (LAN)'),
            $prod && ! $secure ? 'Set SESSION_SECURE_COOKIE=true so the session cookie is HTTPS-only.' : null);

        // Login throttling + security headers (always on in this build)
        $out[] = $this->check('throttle', 'Login rate-limiting', 'ok', 'Locks out after 5 failed tries');
        $out[] = $this->check('headers', 'Security headers', 'ok', 'Clickjacking / sniffing protection on');

        // Default demo accounts still present?
        $demo = User::whereIn('email', self::DEMO_EMAILS)->count();
        $out[] = $this->check('demo', 'Demo accounts removed', $demo > 0 ? 'warn' : 'ok',
            $demo > 0 ? "{$demo} still present" : 'None',
            $demo > 0 ? 'Delete the seeded demo logins (or change their passwords) before go-live.' : null);

        // Backup encryption
        $pw = (string) env('BACKUP_PASSWORD', '') !== '';
        $out[] = $this->check('backup', 'Backups encrypted', $pw ? 'ok' : 'warn', $pw ? 'Encrypted' : 'Unencrypted',
            $pw ? null : 'Set BACKUP_PASSWORD so database backups are encrypted at rest.');

        return $out;
    }

    private function twoFactor(): array
    {
        $total = User::count();
        $enabled = User::whereNotNull('two_factor_confirmed_at')->count();
        $admins = User::role('admin')->count();
        $adminsOn = User::role('admin')->whereNotNull('two_factor_confirmed_at')->count();

        return [
            'enabled' => $enabled,
            'total' => $total,
            'admins' => $admins,
            'admins_on' => $adminsOn,
            'status' => $adminsOn < $admins ? 'warn' : ($enabled < $total ? 'info' : 'ok'),
            'advice' => $adminsOn < $admins
                ? 'At least one administrator has no two-factor protection. Admin accounts can do everything — enable 2FA on each one from its owner’s Profile page.'
                : 'Encourage every staff member to turn on two-factor from their Profile page.',
        ];
    }

    private function check(string $key, string $label, string $status, string $detail, ?string $advice = null): array
    {
        return compact('key', 'label', 'status', 'detail', 'advice');
    }

    private function overall(array $checks): string
    {
        foreach ($checks as $c) {
            if ($c['status'] === 'fail') {
                return 'critical';
            }
        }
        foreach ($checks as $c) {
            if ($c['status'] === 'warn') {
                return 'attention';
            }
        }

        return 'healthy';
    }
}
