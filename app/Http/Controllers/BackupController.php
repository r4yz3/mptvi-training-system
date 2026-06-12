<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    /** Backups contain PII — admin (settings cap) only, and never web-served except via these routes. */
    private function dir(): string
    {
        return Setting::backupDir();
    }

    private const NAME = '/^mptvi-backup-\d{8}-\d{6}\.tar\.gz(\.enc)?$/';

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        $files = collect(glob($this->dir() . '/mptvi-backup-*.tar.gz*'))
            ->sortDesc()
            ->map(fn ($path) => [
                'name' => basename($path),
                'size' => $this->fmt(filesize($path)),
                'bytes' => filesize($path),
                'created' => date('M j, Y · g:i A', filemtime($path)),
                'encrypted' => str_ends_with($path, '.enc'),
            ])->values();

        $time = Setting::get('backup_time', '17:00');
        $enabled = Setting::get('backup_enabled', '1') !== '0';
        $dir = $this->dir();

        return Inertia::render('Settings/Backups', [
            'backups' => $files,
            'schedule' => ['time' => $time, 'enabled' => $enabled],
            'location' => [
                'path' => $dir,
                'is_default' => trim((string) Setting::get('backup_dir', '')) === '',
                'writable' => is_dir($dir) && is_writable($dir),
            ],
            'stats' => [
                'count' => $files->count(),
                'total' => $this->fmt((int) $files->sum('bytes')),
                'last' => $files->first()['created'] ?? null,
                'encrypted' => env('BACKUP_PASSWORD', '') !== '',
                'schedule' => $enabled ? ('Daily · ' . $this->prettyTime($time)) : 'Disabled',
            ],
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        try {
            Artisan::call('backup:run');
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Backup created.');
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $data = $request->validate([
            'time' => ['required', 'date_format:H:i'],
            'enabled' => ['boolean'],
        ]);

        Setting::put('backup_time', $data['time']);
        Setting::put('backup_enabled', $request->boolean('enabled') ? '1' : '0');

        return back()->with('success', 'Backup schedule updated.');
    }

    /** Set the folder where backups are saved (e.g. another drive). Empty = default storage/backups. */
    public function updatePath(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $data = $request->validate(['path' => ['nullable', 'string', 'max:255']]);
        $path = trim((string) ($data['path'] ?? ''));

        if ($path === '') {
            Setting::put('backup_dir', '');

            return back()->with('success', 'Backups will save to the default folder.');
        }

        $path = rtrim($path, "/\\");

        // Create it if missing.
        if (! is_dir($path) && ! @mkdir($path, 0775, true)) {
            return back()->with('error', "Could not create the folder: {$path}");
        }
        if (! is_writable($path)) {
            return back()->with('error', "The folder is not writable: {$path}");
        }
        // Never inside the public web root — backups contain PII and must not be web-served.
        $real = realpath($path);
        if ($real !== false && str_starts_with($real, (string) realpath(public_path()))) {
            return back()->with('error', 'Choose a folder outside the public web folder — backups must not be web-accessible.');
        }

        Setting::put('backup_dir', $path);

        return back()->with('success', 'Backup folder updated.');
    }

    public function download(Request $request, string $name): BinaryFileResponse
    {
        abort_unless($request->user()->can('settings'), 403);
        $path = $this->safePath($name);

        return response()->download($path);
    }

    public function destroy(Request $request, string $name): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);
        File::delete($this->safePath($name));

        return back()->with('success', 'Backup deleted.');
    }

    /** Resolve a backup name safely (no traversal) or 404. */
    private function safePath(string $name): string
    {
        abort_unless(preg_match(self::NAME, $name), 404);
        $path = $this->dir() . '/' . $name;
        abort_unless(is_file($path), 404);

        return $path;
    }

    private function fmt(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        return round($bytes / 1024, 1) . ' KB';
    }

    /** "17:00" → "5:00 PM" */
    private function prettyTime(string $hm): string
    {
        return \Carbon\Carbon::createFromFormat('H:i', $hm)->format('g:i A');
    }
}
