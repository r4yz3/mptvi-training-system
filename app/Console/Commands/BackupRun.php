<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Zero-dependency encrypted backup of the database + uploaded files + .env.
 *
 * - SQLite is snapshotted consistently via `VACUUM INTO` (never a raw copy).
 * - MySQL is dumped via mysqldump, with a pure-PHP fallback.
 * - The archive is AES-256-CBC encrypted in the OpenSSL "Salted__" PBKDF2 format,
 *   so it can be restored with a standard `openssl` command even without the app:
 *     openssl enc -d -aes-256-cbc -pbkdf2 -iter 100000 -in <file>.enc -k "$BACKUP_PASSWORD" | tar xzf -
 * - Old backups are pruned (keep N daily + weekly Sundays).
 */
class BackupRun extends Command
{
    protected $signature = 'backup:run {--keep=14 : Daily backups to keep} {--weeks=8 : Weekly (Sunday) backups to keep}';

    protected $description = 'Create an encrypted backup of the database, uploaded files and .env, then prune old backups.';

    public function handle(): int
    {
        $stamp = now()->format('Ymd-His');
        $backupDir = \App\Models\Setting::backupDir();
        File::ensureDirectoryExists($backupDir);

        $stage = storage_path("app/_backup-{$stamp}");
        File::ensureDirectoryExists($stage);

        try {
            $this->info("Backing up database…");
            $this->dumpDatabase($stage);

            $this->info('Collecting uploaded files & config…');
            $this->stageFiles($stage);

            $this->info('Archiving…');
            $tarGz = $this->makeArchive($stage, $backupDir, $stamp);

            $final = $this->encrypt($tarGz);

            $size = $this->fmt(filesize($final));
            $this->info('Backup written: ' . basename($final) . " ({$size})");

            $this->prune($backupDir, (int) $this->option('keep'), (int) $this->option('weeks'));
        } catch (\Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            report($e);

            return self::FAILURE;
        } finally {
            File::deleteDirectory($stage);
        }

        return self::SUCCESS;
    }

    /* ----------------------------------------------------------------- DB */

    private function dumpDatabase(string $stage): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $src = config('database.connections.sqlite.database');
            $dest = "{$stage}/database.sqlite";
            // Consistent snapshot of a live DB (respects WAL).
            DB::statement("VACUUM INTO '" . str_replace("'", "''", $dest) . "'");

            return;
        }

        // MySQL / MariaDB
        $dest = "{$stage}/database.sql";
        if (! $this->mysqldump($dest)) {
            $this->warn('  mysqldump unavailable — using PHP fallback dump.');
            $this->phpSqlDump($dest);
        }
    }

    private function mysqldump(string $dest): bool
    {
        $c = config('database.connections.' . config('database.default'));
        $cmd = sprintf(
            'mysqldump --host=%s --port=%s --user=%s %s --single-transaction --quick --skip-lock-tables %s > %s 2>%s',
            escapeshellarg($c['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($c['port'] ?? 3306)),
            escapeshellarg($c['username'] ?? 'root'),
            ! empty($c['password']) ? '--password=' . escapeshellarg($c['password']) : '',
            escapeshellarg($c['database']),
            escapeshellarg($dest),
            escapeshellarg($dest . '.err'),
        );
        @exec($cmd, $out, $code);
        @unlink($dest . '.err');

        return $code === 0 && is_file($dest) && filesize($dest) > 0;
    }

    private function phpSqlDump(string $dest): void
    {
        $fh = fopen($dest, 'w');
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
        foreach (DB::select('SHOW TABLES') as $row) {
            $table = array_values((array) $row)[0];
            $create = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");
            fwrite($fh, "\nDROP TABLE IF EXISTS `{$table}`;\n" . end($create) . ";\n");
            foreach (DB::cursor("SELECT * FROM `{$table}`") as $record) {
                $vals = array_map(fn ($v) => $v === null ? 'NULL' : DB::getPdo()->quote((string) $v), (array) $record);
                fwrite($fh, "INSERT INTO `{$table}` VALUES (" . implode(',', $vals) . ");\n");
            }
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
    }

    /* -------------------------------------------------------------- files */

    private function stageFiles(string $stage): void
    {
        // Uploaded files (private + public disks)
        foreach (['app/private', 'app/public'] as $rel) {
            $src = storage_path($rel);
            if (File::isDirectory($src)) {
                File::copyDirectory($src, "{$stage}/storage/{$rel}");
            }
        }
        // Public logos (overwritable branding)
        File::ensureDirectoryExists("{$stage}/public");
        foreach (['mptvi-logo.png', 'magsaysay-logo.png'] as $logo) {
            if (is_file(public_path($logo))) {
                File::copy(public_path($logo), "{$stage}/public/{$logo}");
            }
        }
        // Environment (secrets needed to run/restore)
        if (is_file(base_path('.env'))) {
            File::copy(base_path('.env'), "{$stage}/.env");
        }
    }

    private function makeArchive(string $stage, string $backupDir, string $stamp): string
    {
        $tar = "{$backupDir}/mptvi-backup-{$stamp}.tar";
        @unlink($tar);
        @unlink($tar . '.gz');

        $phar = new \PharData($tar);
        $phar->buildFromDirectory($stage);
        $phar->compress(\Phar::GZ); // → .tar.gz
        unset($phar);
        @unlink($tar);

        return "{$tar}.gz";
    }

    /* ---------------------------------------------------------- encryption */

    private function encrypt(string $tarGz): string
    {
        $password = (string) env('BACKUP_PASSWORD', '');
        if ($password === '') {
            $this->warn('  BACKUP_PASSWORD not set — backup left UNENCRYPTED. Set it in .env for PII safety.');

            return $tarGz;
        }

        $plain = file_get_contents($tarGz);
        $salt = random_bytes(8);
        $keyiv = hash_pbkdf2('sha256', $password, $salt, 100000, 48, true);
        $key = substr($keyiv, 0, 32);
        $iv = substr($keyiv, 32, 16);
        $cipher = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        // OpenSSL "Salted__" container → decryptable with the openssl CLI.
        $enc = $tarGz . '.enc';
        file_put_contents($enc, "Salted__" . $salt . $cipher);
        @unlink($tarGz);

        return $enc;
    }

    /* ------------------------------------------------------------- pruning */

    private function prune(string $dir, int $keepDaily, int $keepWeeks): void
    {
        $files = collect(glob("{$dir}/mptvi-backup-*.tar.gz*"))
            ->sortDesc()
            ->values();

        $kept = 0;
        $weeklyKept = 0;
        $deleted = 0;

        foreach ($files as $i => $path) {
            if ($i < $keepDaily) {
                $kept++;

                continue;
            }
            // Keep Sunday backups as weeklies, up to $keepWeeks.
            if (preg_match('/(\d{8})-\d{6}/', basename($path), $m)) {
                $isSunday = \Carbon\Carbon::createFromFormat('Ymd', $m[1])->isSunday();
                if ($isSunday && $weeklyKept < $keepWeeks) {
                    $weeklyKept++;

                    continue;
                }
            }
            @unlink($path);
            $deleted++;
        }

        $this->line("  Retained {$kept} daily + {$weeklyKept} weekly; pruned {$deleted}.");
    }

    private function fmt(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        return round($bytes / 1024, 1) . ' KB';
    }
}
