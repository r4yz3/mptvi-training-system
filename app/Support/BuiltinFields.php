<?php

namespace App\Support;

use App\Models\FieldSetting;

class BuiltinFields
{
    /** Registry merged with admin overrides → effective built-in field config. */
    public static function all(): array
    {
        $settings = FieldSetting::all()->keyBy('key');

        return collect(config('builtin_fields.fields'))->values()->map(function ($f, $i) use ($settings) {
            $s = $settings->get($f['key']);
            $locked = $f['locked'] ?? false;

            return [
                'key' => $f['key'],
                'label' => ($s && $s->label) ? $s->label : $f['label'],
                'default_label' => $f['label'],
                // Effective category — admin can move a built-in field anywhere.
                'section' => ($s && $s->section) ? $s->section : $f['section'],
                'default_section' => $f['section'],
                // Ordering: explicit override, else registry index (built-ins first).
                'position' => $s?->position ?? $i,
                'locked' => $locked,
                // Locked fields are always shown & required (system-critical).
                'enabled' => $locked ? true : ($s?->enabled ?? true),
                'required' => $locked ? true : ($s?->required ?? false),
                'deleted' => $locked ? false : (bool) ($s?->deleted ?? false),
                // Render descriptor for the data-driven registration form.
                'widget' => $f['widget'] ?? 'text',
                'source' => $f['source'] ?? null,
                'blank' => $f['blank'] ?? false,
                'blankLabel' => $f['blankLabel'] ?? null,
                'placeholder' => $f['placeholder'] ?? null,
                'colspan' => $f['colspan'] ?? null,
                'signatory' => $f['signatory'] ?? null,
            ];
        })->all();
    }

    /** Keyed by field key. */
    public static function map(): array
    {
        return collect(self::all())->keyBy('key')->all();
    }
}
