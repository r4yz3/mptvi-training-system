<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Simple key/value organisation settings, overriding the config/lpf.php defaults.
 * Used for the accredited assessor and the LPF/certificate/OR signatories.
 */
class Setting extends Model
{
    protected $guarded = ['id'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value !== null && $value !== '' ? $value : $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Decode a JSON-array setting, or return $default when absent/invalid. */
    public static function json(string $key, array $default = []): array
    {
        $raw = static::get($key);
        if (! $raw) {
            return $default;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : $default;
    }

    public static function putJson(string $key, array $value): void
    {
        static::put($key, json_encode(array_values($value)));
    }

    /** Brand primary colour hex, or '' to use the built-in navy. */
    public static function brandColor(): string
    {
        return (string) static::get('brand_color', '');
    }

    /** Folder where backups are written. Admin-configurable; defaults to storage/backups. */
    public static function backupDir(): string
    {
        $custom = trim((string) static::get('backup_dir', ''));

        return $custom !== '' ? rtrim($custom, "/\\") : storage_path('backups');
    }

    /** Lists (config/lpf.php keys) that admins may edit from the UI. */
    public const EDITABLE_LISTS = [
        'civil_status', 'emp_status', 'emp_type', 'education', 'scholarship',
        'classifications', 'blood_types', 'regions',
    ];

    /**
     * Merge saved overrides over the file configs so every config('requirements')
     * / config('lpf.*') / config('academic.*') read reflects admin edits.
     * Called from AppServiceProvider::boot() (guarded by table existence).
     */
    public static function applyConfigOverrides(): void
    {
        $rows = static::query()->pluck('value', 'key');

        // Documentary requirements
        if ($r = ($rows['requirements'] ?? null)) {
            $decoded = json_decode($r, true);
            if (is_array($decoded) && $decoded) {
                config(['requirements' => $decoded]);
            }
        }

        // Reference / dropdown lists
        foreach (self::EDITABLE_LISTS as $key) {
            if ($v = ($rows["lpf_{$key}"] ?? null)) {
                $decoded = json_decode($v, true);
                if (is_array($decoded) && $decoded) {
                    config(["lpf.{$key}" => array_values($decoded)]);
                }
            }
        }

        // Educational attainment grid — levels are key+label rows (not a flat
        // list), statuses a flat list; both edited at Settings → Education grid.
        foreach (['education_levels', 'education_statuses'] as $key) {
            if ($v = ($rows["lpf_{$key}"] ?? null)) {
                $decoded = json_decode($v, true);
                if (is_array($decoded) && $decoded) {
                    config(["lpf.{$key}" => array_values($decoded)]);
                }
            }
        }

        // Grading system — weighted components + passing grade (Settings → Grading).
        if ($v = ($rows['grading_components'] ?? null)) {
            $decoded = json_decode($v, true);
            if (is_array($decoded) && $decoded) {
                config(['grading.components' => array_values($decoded)]);
            }
        }
        if (($v = ($rows['grading_passing'] ?? null)) !== null && $v !== '') {
            config(['grading.passing' => (int) $v]);
        }

        // Academic defaults & numbering
        $map = [
            'acad_school_year' => 'academic.school_year',
            'acad_default_session' => 'academic.default_session',
            'acad_default_fee' => 'academic.default_fee',
            'acad_age_min' => 'academic.age_min',
            'acad_age_max' => 'academic.age_max',
            'cert_prefix' => 'academic.cert_prefix',
        ];
        foreach ($map as $settingKey => $configKey) {
            if (($v = ($rows[$settingKey] ?? null)) !== null && $v !== '') {
                config([$configKey => $v]);
            }
        }
    }

    /** Accredited assessor — falls back to '' so callers can decide a label. */
    public static function assessor(): string
    {
        return (string) static::get('assessor', '');
    }

    /**
     * Institution profile used across printed documents (certificate, LPF, ID, OR).
     * Stored overrides win over these defaults.
     */
    public static function institution(): array
    {
        return [
            'name' => static::get('org_name', 'Maximino Pellerin Sr. Technical and Vocational Institute'),
            'short_name' => static::get('org_short_name', 'MPTVI'),
            'office' => static::get('org_office', 'Public Employment Service Office (PESO)'),
            'address' => static::get('org_address', 'Magsaysay, Davao del Sur'),
            'contact' => static::get('org_contact', ''),
            'email' => static::get('org_email', ''),
        ];
    }

    /**
     * Signatories shaped like config('lpf.signatories'), with stored overrides
     * winning over the config defaults.
     */
    public static function signatories(): array
    {
        $defaults = config('lpf.signatories');

        return [
            'checked_by' => [
                'name' => static::get('signatory_checked_name', $defaults['checked_by']['name']),
                'title' => static::get('signatory_checked_title', $defaults['checked_by']['title']),
            ],
            'approved_by' => [
                'name' => static::get('signatory_approved_name', $defaults['approved_by']['name']),
                'title' => static::get('signatory_approved_title', $defaults['approved_by']['title']),
            ],
        ];
    }
}
