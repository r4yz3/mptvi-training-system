<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Live system health + hardware sizing advice for the Settings → System panel.
 *
 * Designed for the on-site Windows/Laragon install (single-PC LAN server) but
 * degrades gracefully on Linux/VPS and when shell access is disabled. Hardware
 * probing is best-effort: anything it can't read is reported as "unknown" and
 * the advice adapts rather than failing.
 */
class SystemHealth
{
    /** Build the full report consumed by the Settings health panel. */
    public function report(): array
    {
        $resources = $this->resources();
        $checks = $this->checks($resources);
        $advice = $this->advice($resources);

        return [
            'overall' => $this->overall($checks, $advice),
            'resources' => $resources,
            'checks' => $checks,
            'advice' => $advice,
            'priority' => 'For this app, the upgrades that help most are — in order — an SSD, then more RAM, then a faster CPU.',
        ];
    }

    /* ----------------------------- hardware ----------------------------- */

    private function resources(): array
    {
        $os = PHP_OS_FAMILY;
        $disk = $this->disk();
        [$cpu, $ram, $media] = match ($os) {
            'Windows' => $this->windowsMetrics(),
            'Linux' => $this->linuxMetrics(),
            default => [['model' => null, 'cores' => $this->cores(), 'load_pct' => null], null, 'unknown'],
        };

        if ($media !== 'unknown' && $media !== null) {
            $disk['media'] = $media;
        }

        return [
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'host' => php_uname('n'),
            'cpu' => $cpu,
            'ram' => $ram,
            'disk' => $disk,
        ];
    }

    private function disk(): array
    {
        $root = base_path();
        $total = @disk_total_space($root) ?: 0;
        $free = @disk_free_space($root) ?: 0;
        $usedPct = $total > 0 ? (int) round(($total - $free) / $total * 100) : null;

        return [
            'total_gb' => $total ? round($total / 1073741824, 1) : null,
            'free_gb' => $free ? round($free / 1073741824, 1) : null,
            'used_pct' => $usedPct,
            'media' => 'unknown',
        ];
    }

    private function cores(): ?int
    {
        $env = getenv('NUMBER_OF_PROCESSORS');

        return $env !== false && (int) $env > 0 ? (int) $env : null;
    }

    /** One batched PowerShell CIM call — avoids the removed `wmic` on new Win11. */
    private function windowsMetrics(): array
    {
        $cpu = ['model' => null, 'cores' => $this->cores(), 'load_pct' => null];
        $ram = null;
        $media = 'unknown';

        if (! $this->canShell()) {
            return [$cpu, $ram, $media];
        }

        // NOWDOC so PHP does not interpolate the PowerShell $variables.
        $script = <<<'PS'
$ErrorActionPreference='SilentlyContinue'
$os=Get-CimInstance Win32_OperatingSystem
$cpu=Get-CimInstance Win32_Processor | Select-Object -First 1
try { $media=((Get-PhysicalDisk).MediaType | Where-Object {$_}) -join '|' } catch { $media='' }
Write-Output ("{0}`t{1}`t{2}`t{3}`t{4}`t{5}" -f $cpu.Name,$cpu.NumberOfLogicalProcessors,$cpu.LoadPercentage,$os.TotalVisibleMemorySize,$os.FreePhysicalMemory,$media)
PS;
        $enc = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));
        $out = @shell_exec('powershell -NoProfile -NonInteractive -EncodedCommand ' . $enc . ' 2>NUL');

        if (is_string($out) && str_contains($out, "\t")) {
            [$model, $cores, $load, $totalKb, $freeKb, $mediaRaw] = array_pad(explode("\t", trim($out)), 6, '');
            $cpu['model'] = $model !== '' ? trim($model) : $cpu['model'];
            $cpu['cores'] = (int) $cores ?: $cpu['cores'];
            $cpu['load_pct'] = is_numeric($load) ? (int) $load : null;
            $ram = $this->ramFromKb($totalKb, $freeKb);
            $media = $this->normalizeMedia($mediaRaw);
        }

        return [$cpu, $ram, $media];
    }

    private function linuxMetrics(): array
    {
        $cpu = ['model' => null, 'cores' => $this->cores(), 'load_pct' => null];
        $ram = null;
        $media = 'unknown';

        // CPU model + cores from /proc/cpuinfo
        if (is_readable('/proc/cpuinfo')) {
            $info = (string) @file_get_contents('/proc/cpuinfo');
            if (preg_match('/model name\s*:\s*(.+)/', $info, $m)) {
                $cpu['model'] = trim($m[1]);
            }
            $count = substr_count($info, "\nprocessor");
            if ($count > 0) {
                $cpu['cores'] = $count + 1;
            }
        }

        // Load average → percent of cores
        if (function_exists('sys_getloadavg')) {
            $la = sys_getloadavg();
            if (is_array($la) && ($cpu['cores'] ?? 0) > 0) {
                $cpu['load_pct'] = min(100, (int) round($la[0] / $cpu['cores'] * 100));
            }
        }

        // Memory from /proc/meminfo (kB)
        if (is_readable('/proc/meminfo')) {
            $mem = (string) @file_get_contents('/proc/meminfo');
            $total = preg_match('/MemTotal:\s*(\d+)/', $mem, $t) ? (int) $t[1] : 0;
            $avail = preg_match('/MemAvailable:\s*(\d+)/', $mem, $a) ? (int) $a[1] : 0;
            if ($total > 0) {
                $ram = $this->ramFromKb($total, $avail);
            }
        }

        // SSD vs HDD via rotational flag of block devices
        $rot = glob('/sys/block/*/queue/rotational') ?: [];
        $flags = array_map(fn ($p) => trim((string) @file_get_contents($p)), $rot);
        if ($flags !== []) {
            $media = in_array('1', $flags, true) ? 'HDD' : 'SSD';
        }

        return [$cpu, $ram, $media];
    }

    private function ramFromKb(string|int $totalKb, string|int $freeKb): ?array
    {
        $total = (int) $totalKb;
        $free = (int) $freeKb;
        if ($total <= 0) {
            return null;
        }

        return [
            'total_gb' => round($total / 1048576, 1),
            'free_gb' => round($free / 1048576, 1),
            'used_pct' => (int) round(($total - $free) / $total * 100),
        ];
    }

    private function normalizeMedia(string $raw): string
    {
        $raw = strtoupper($raw);
        if (str_contains($raw, 'HDD')) {
            return 'HDD';
        }
        if (str_contains($raw, 'SSD')) {
            return 'SSD';
        }

        return 'unknown';
    }

    private function canShell(): bool
    {
        if (! function_exists('shell_exec')) {
            return false;
        }
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('shell_exec', $disabled, true);
    }

    /* ----------------------------- checks ----------------------------- */

    private function checks(array $r): array
    {
        $out = [];

        // Database
        try {
            $pdo = DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();
            $ver = (string) $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $out[] = $this->check('database', 'Database connection', 'ok', trim(strtoupper($driver) . ' ' . $ver));
        } catch (\Throwable $e) {
            $out[] = $this->check('database', 'Database connection', 'fail', 'Cannot connect');
        }

        // Disk space
        $diskPct = $r['disk']['used_pct'];
        if ($diskPct === null) {
            $out[] = $this->check('disk', 'Disk space', 'warn', 'Unknown');
        } else {
            $free = $r['disk']['free_gb'];
            $status = $diskPct >= 92 ? 'fail' : ($diskPct >= 80 ? 'warn' : 'ok');
            $out[] = $this->check('disk', 'Disk space', $status, "{$free} GB free ({$diskPct}% used)");
        }

        // Memory
        if ($r['ram'] === null) {
            $out[] = $this->check('memory', 'Memory', 'warn', 'Unknown');
        } else {
            $used = $r['ram']['used_pct'];
            $status = $used >= 90 ? 'fail' : ($used >= 80 ? 'warn' : 'ok');
            $out[] = $this->check('memory', 'Memory', $status, "{$r['ram']['free_gb']} GB free ({$used}% used)");
        }

        // Storage writable
        $writable = is_writable(storage_path('framework')) && is_writable(base_path('bootstrap/cache'));
        $out[] = $this->check('storage', 'App storage writable', $writable ? 'ok' : 'fail', $writable ? 'OK' : 'Not writable');

        // Latest backup
        $backups = glob(storage_path('backups/*')) ?: [];
        if ($backups === []) {
            $out[] = $this->check('backup', 'Latest backup', 'warn', 'No backups yet');
        } else {
            $newest = max(array_map('filemtime', $backups));
            $days = (int) floor((time() - $newest) / 86400);
            $status = $days > 8 ? 'warn' : 'ok';
            $label = $days <= 0 ? 'Today' : ($days === 1 ? 'Yesterday' : "{$days} days ago");
            $out[] = $this->check('backup', 'Latest backup', $status, $label);
        }

        // Debug mode (must be off in production)
        $debug = (bool) config('app.debug');
        $prod = app()->environment('production');
        $out[] = $this->check('debug', 'Debug mode', $debug && $prod ? 'warn' : 'ok', $debug ? 'On' : 'Off');

        // PHP version
        $phpOk = version_compare(PHP_VERSION, '8.3.0', '>=');
        $out[] = $this->check('php', 'PHP version', $phpOk ? 'ok' : 'warn', PHP_VERSION);

        return $out;
    }

    private function check(string $key, string $label, string $status, string $detail): array
    {
        return compact('key', 'label', 'status', 'detail');
    }

    /* ----------------------------- advice ----------------------------- */

    private function advice(array $r): array
    {
        return array_values(array_filter([
            $this->diskAdvice($r['disk']),
            $this->ramAdvice($r['ram']),
            $this->cpuAdvice($r['cpu']),
        ]));
    }

    private function diskAdvice(array $disk): array
    {
        $media = $disk['media'];
        $free = $disk['free_gb'];
        $used = $disk['used_pct'];

        if ($media === 'HDD') {
            return $this->tip('Storage', 'recommended', 'Switch to an SSD',
                'This PC runs on a spinning hard drive (HDD). Replacing it with an SSD is the single biggest speed-up for a database app like this — registration, search and reports will feel far more responsive. Even a small 256–500 GB SSD for the system + app is enough.');
        }

        if ($used !== null && ($used >= 92 || ($free !== null && $free < 5))) {
            return $this->tip('Storage', 'recommended', 'Free up disk space',
                "Only {$free} GB free ({$used}% used). The database and encrypted backups need room to grow — clear space or fit a larger drive soon to avoid failed backups and write errors.");
        }

        if ($used !== null && $used >= 80) {
            return $this->tip('Storage', 'consider', 'Plan for more disk space',
                "{$free} GB free ({$used}% used). Not urgent, but plan for a larger drive within the year as records and backups accumulate.");
        }

        if ($media === 'SSD') {
            return $this->tip('Storage', 'ok', 'SSD — ideal',
                "Running on an SSD with {$free} GB free. That's exactly what this app wants; no storage upgrade needed.");
        }

        return $this->tip('Storage', 'consider', 'Confirm the drive is an SSD',
            "Couldn't detect the drive type. If this PC still uses a spinning hard disk, switching to an SSD is the best upgrade you can make for speed.");
    }

    private function ramAdvice(?array $ram): array
    {
        if ($ram === null) {
            return $this->tip('Memory (RAM)', 'consider', 'Check installed RAM',
                "Couldn't read memory. If this PC has under 8 GB of RAM, adding more is worthwhile — MySQL, PHP and Windows together are cramped below 8 GB.");
        }

        $total = $ram['total_gb'];
        $used = $ram['used_pct'];

        if ($total < 7.5) {
            return $this->tip('Memory (RAM)', 'recommended', 'Upgrade to at least 8 GB',
                "This PC has about {$total} GB of RAM. MySQL, PHP and Windows together are tight under 8 GB and will slow down as the database grows. 8 GB is a comfortable minimum; 16 GB is ideal if the PC also runs other software.");
        }

        if ($used >= 88) {
            return $this->tip('Memory (RAM)', 'recommended', 'Add more RAM',
                "Memory is {$used}% used right now. Either close other apps running on this PC or add RAM (16 GB) so the database and server always have headroom.");
        }

        if ($total < 15) {
            return $this->tip('Memory (RAM)', 'ok', '8 GB is enough',
                "About {$total} GB installed — fine for this app on a single-office server. Move to 16 GB only if you plan to run other heavy software on the same PC.");
        }

        return $this->tip('Memory (RAM)', 'ok', 'Plenty of RAM',
            "About {$total} GB installed ({$used}% in use). More than enough for this app.");
    }

    private function cpuAdvice(array $cpu): array
    {
        $cores = $cpu['cores'];
        $load = $cpu['load_pct'];
        $model = $cpu['model'] ? trim($cpu['model']) : 'the current CPU';

        if ($load !== null && $load >= 85) {
            return $this->tip('Processor (CPU)', 'recommended', 'CPU is running hot',
                "The processor is at {$load}% load. If that's constant, a faster CPU would help — but first confirm RAM and disk aren't the real bottleneck, which is usually the case for this kind of app.");
        }

        if ($cores !== null && $cores < 4) {
            return $this->tip('Processor (CPU)', 'consider', 'A 4-core CPU is smoother for many users',
                "This PC has {$cores} core(s). That works for a handful of staff at once. If many people use the system simultaneously, a 4-core (or better) CPU helps — though RAM and an SSD make a bigger difference for this app.");
        }

        if ($cores !== null) {
            return $this->tip('Processor (CPU)', 'ok', 'CPU is sufficient',
                "{$cores} cores on {$model}. The CPU is rarely the limit for a database app like this — no change needed.");
        }

        return $this->tip('Processor (CPU)', 'ok', 'CPU likely sufficient',
            'For a small office database app the CPU is rarely the bottleneck; prioritise an SSD and RAM first.');
    }

    private function tip(string $component, string $severity, string $title, string $detail): array
    {
        return compact('component', 'severity', 'title', 'detail');
    }

    /* ----------------------------- overall ----------------------------- */

    private function overall(array $checks, array $advice): string
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
        foreach ($advice as $a) {
            if ($a['severity'] === 'recommended') {
                return 'attention';
            }
        }

        return 'healthy';
    }
}
