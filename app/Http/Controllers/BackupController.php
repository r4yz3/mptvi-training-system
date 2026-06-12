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
        return storage_path('backups');
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

        return Inertia::render('Settings/Backups', [
            'backups' => $files,
            'schedule' => ['time' => $time, 'enabled' => $enabled],
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
