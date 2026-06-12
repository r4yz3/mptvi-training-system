<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily encrypted backup of DB + uploads + .env. Time is admin-configurable
// (Settings → Backups); defaults to 17:00 (5 PM) server time.
$backupTime = '17:00';
$backupEnabled = true;
try {
    if (Schema::hasTable('settings')) {
        $saved = \App\Models\Setting::get('backup_time', '17:00');
        if (preg_match('/^\d{2}:\d{2}$/', (string) $saved)) {
            $backupTime = $saved;
        }
        $backupEnabled = \App\Models\Setting::get('backup_enabled', '1') !== '0';
    }
} catch (\Throwable $e) {
    // settings table not ready (early migrate) — fall back to defaults
}
if ($backupEnabled) {
    Schedule::command('backup:run')->dailyAt($backupTime)->withoutOverlapping();
}
