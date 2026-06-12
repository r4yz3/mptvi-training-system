<?php

namespace App\Support;

use App\Models\FieldSetting;

class BuiltinFields
{
    /** Registry merged with admin overrides → effective built-in field config. */
    public static function all(): array
    {
        $settings = FieldSetting::all()->keyBy('key');

        return collect(config('builtin_fields.fields'))->map(function ($f) use ($settings) {
            $s = $settings->get($f['key']);
            $locked = $f['locked'] ?? false;

            return [
                'key' => $f['key'],
                'label' => ($s && $s->label) ? $s->label : $f['label'],
                'default_label' => $f['label'],
                'section' => $f['section'],
                'locked' => $locked,
                // Locked fields are always shown & required (system-critical).
                'enabled' => $locked ? true : ($s?->enabled ?? true),
                'required' => $locked ? true : ($s?->required ?? false),
            ];
        })->all();
    }

    /** Keyed by field key. */
    public static function map(): array
    {
        return collect(self::all())->keyBy('key')->all();
    }
}
