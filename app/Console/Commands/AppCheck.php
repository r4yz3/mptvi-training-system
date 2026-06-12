<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Installation health check. Verifies PHP, extensions, .env, database,
 * migrations, storage, built assets and backups, and prints a pass/fail
 * report with guidance. Exit code is non-zero if any check FAILs.
 *
 *   php artisan app:check
 */
class AppCheck extends Command
{
    protected $signature = 'app:check';

    protected $description = 'Diagnose the installation (PHP, extensions, .env, database, migrations, storage, assets, backups).';

    private int $fails = 0;

    private int $warns = 0;

    public function handle(): int
    {
        $this->line('');
        $this->line('  <options=bold>MPTVI — installation check</>');
        $this->line('  ' . str_repeat('=', 48));

        $this->checkPhp();
        $this->checkExtensions();
        $this->checkEnv();
        $this->checkDatabase();
        $this->checkMigrations();
        $this->checkStorage();
        $this->checkAssets();
        $this->checkBackups();

        $this->line('  ' . str_repeat('-', 48));
        if ($this->fails === 0 && $this->warns === 0) {
            $this->line('  <fg=black;bg=green> ALL GOOD </> Everything checks out.');
        } elseif ($this->fails === 0) {
            $this->line("  <fg=black;bg=yellow> {$this->warns} WARNING(S) </> The app should run — review the warnings above.");
        } else {
            $this->line("  <fg=white;bg=red> {$this->fails} PROBLEM(S) </> Fix the FAIL items above, then re-run: php artisan app:check");
        }
        $this->line('');

        return $this->fails > 0 ? self::FAILURE : self::SUCCESS;
    }

    /* ----------------------------------------------------------------- */

    private function result(string $status, string $label, string $detail = ''): void
    {
        $tag = match ($status) {
            'ok' => '<fg=black;bg=green> OK </>',
            'warn' => '<fg=black;bg=yellow>WARN</>',
            default => '<fg=white;bg=red>FAIL</>',
        };
        if ($status === 'warn') {
            $this->warns++;
        }
        if ($status === 'fail') {
            $this->fails++;
        }
        $this->line("  {$tag}  " . str_pad($label, 26) . ($detail !== '' ? "  {$detail}" : ''));
    }

    private function checkPhp(): void
    {
        $ok = version_compare(PHP_VERSION, '8.3.0', '>=');
        $this->result($ok ? 'ok' : 'fail', 'PHP version', PHP_VERSION . ($ok ? '' : ' — needs 8.3+'));
    }

    private function checkExtensions(): void
    {
        $required = [
            'pdo_sqlite' => 'database', 'mbstring' => '', 'openssl' => 'backup encryption',
            'fileinfo' => 'uploads', 'gd' => 'image optimization', 'curl' => '', 'zip' => '',
        ];
        foreach ($required as $ext => $why) {
            $loaded = extension_loaded($ext);
            $this->result($loaded ? 'ok' : 'fail', "ext: {$ext}", $loaded ? '' : 'missing' . ($why ? " — needed for {$why}" : ''));
        }
        $webp = function_exists('imagewebp');
        $this->result($webp ? 'ok' : 'warn', 'GD WebP support', $webp ? '' : 'WebP encode off (JPEG/PNG still optimized)');
    }

    private function checkEnv(): void
    {
        $hasEnv = file_exists(base_path('.env'));
        $this->result($hasEnv ? 'ok' : 'fail', '.env file', $hasEnv ? '' : 'missing — copy deploy/local/env.local.example to .env');

        $key = (string) config('app.key');
        $this->result($key !== '' ? 'ok' : 'fail', 'APP_KEY', $key !== '' ? 'set' : 'empty — run php artisan key:generate');

        $env = app()->environment();
        $this->result($env === 'production' ? 'warn' : 'ok', 'APP_ENV', $env . ($env === 'production' ? ' — forces HTTPS; use "local" for an http LAN' : ''));

        $url = (string) config('app.url');
        $this->result($url !== '' ? 'ok' : 'warn', 'APP_URL', $url !== '' ? $url : 'not set');
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();
            $this->result('ok', 'Database connection', $driver);

            if ($driver === 'sqlite') {
                $path = (string) config('database.connections.sqlite.database');
                if (! is_file($path)) {
                    $this->result('fail', 'SQLite file', 'missing: ' . $path);
                } else {
                    $this->result(is_writable($path) ? 'ok' : 'fail', 'SQLite file', is_writable($path) ? basename($path) : 'not writable: ' . $path);
                }
            }
        } catch (\Throwable $e) {
            $this->result('fail', 'Database connection', $e->getMessage());
        }
    }

    private function checkMigrations(): void
    {
        try {
            if (! Schema::hasTable('migrations')) {
                $this->result('fail', 'Migrations', 'not run — php artisan migrate --force');

                return;
            }
            $ran = DB::table('migrations')->pluck('migration')->all();
            $files = collect(glob(database_path('migrations/*.php')))->map(fn ($f) => basename($f, '.php'))->all();
            $pending = array_diff($files, $ran);
            $this->result(
                empty($pending) ? 'ok' : 'warn',
                'Migrations',
                empty($pending) ? count($ran) . ' applied' : count($pending) . ' pending — run php artisan migrate --force',
            );
            $this->result(Schema::hasTable('settings') ? 'ok' : 'warn', 'Feature tables', Schema::hasTable('settings') ? 'present' : 'settings table missing');
        } catch (\Throwable $e) {
            $this->result('fail', 'Migrations', $e->getMessage());
        }
    }

    private function checkStorage(): void
    {
        $paths = [
            'storage' => storage_path(),
            'storage/framework' => storage_path('framework'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];
        foreach ($paths as $label => $path) {
            $w = is_dir($path) && is_writable($path);
            $this->result($w ? 'ok' : 'fail', "writable: {$label}", $w ? '' : 'NOT writable: ' . $path);
        }
        $link = public_path('storage');
        $this->result(file_exists($link) ? 'ok' : 'warn', 'storage symlink', file_exists($link) ? '' : 'missing — php artisan storage:link');
    }

    private function checkAssets(): void
    {
        $manifest = public_path('build/manifest.json');
        $this->result(file_exists($manifest) ? 'ok' : 'fail', 'Front-end build', file_exists($manifest) ? '' : 'missing — run npm run build');
        foreach (['mptvi-logo.png', 'magsaysay-logo.png'] as $logo) {
            $this->result(file_exists(public_path($logo)) ? 'ok' : 'warn', "logo: {$logo}", file_exists(public_path($logo)) ? '' : 'missing in public/');
        }
    }

    private function checkBackups(): void
    {
        $dir = storage_path('backups');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $w = is_dir($dir) && is_writable($dir);
        $this->result($w ? 'ok' : 'fail', 'backups folder', $w ? '' : 'NOT writable: ' . $dir);

        $pw = (string) env('BACKUP_PASSWORD', '');
        $this->result($pw !== '' ? 'ok' : 'warn', 'BACKUP_PASSWORD', $pw !== '' ? 'set (backups encrypted)' : 'not set — backups are UNENCRYPTED');
    }
}
