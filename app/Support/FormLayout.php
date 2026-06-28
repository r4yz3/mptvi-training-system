<?php

namespace App\Support;

use App\Models\CustomField;
use App\Models\FormSection;

/**
 * Assembles the admin-configured registration form: ordered categories and,
 * within each, the built-in + custom fields interleaved by position. Used by
 * the registration form (visible fields only) and the Form Builder (everything).
 */
class FormLayout
{
    /** Categories in display order. Seeds the table from config on first use. */
    public static function sections(): \Illuminate\Support\Collection
    {
        self::ensureSeeded();

        return FormSection::orderBy('sort_order')->orderBy('id')
            ->get(['id', 'key', 'label', 'note', 'enabled', 'sort_order']);
    }

    /** Every built-in field (incl. hidden/deleted) — for the Form Builder. */
    public static function builtinFields(): array
    {
        return BuiltinFields::all();
    }

    /**
     * The fields the registration form should render, in order: enabled,
     * non-deleted built-ins + enabled custom fields, sorted by position
     * within their (effective) category. Each entry carries a render descriptor.
     */
    public static function formFields(): array
    {
        $builtin = collect(BuiltinFields::all())
            ->filter(fn ($f) => $f['enabled'] && ! $f['deleted'])
            ->map(fn ($f) => [
                'kind' => 'builtin',
                'key' => $f['key'],
                'label' => $f['label'],
                'section' => $f['section'],
                'required' => $f['required'],
                'position' => $f['position'],
                'widget' => $f['widget'],
                'source' => $f['source'],
                'blank' => $f['blank'],
                'blankLabel' => $f['blankLabel'],
                'placeholder' => $f['placeholder'],
                'colspan' => $f['colspan'],
                'signatory' => $f['signatory'],
            ]);

        $custom = CustomField::where('enabled', true)->get()
            ->map(fn (CustomField $f) => [
                'kind' => 'custom',
                'key' => $f->key,
                'label' => $f->label,
                'section' => $f->section,
                'required' => (bool) $f->required,
                // Custom fields sit after built-ins by default; explicit position wins.
                'position' => $f->position ?? (1000 + $f->id),
                'type' => $f->type,
                'options' => $f->options,
                'colspan' => in_array($f->type, ['textarea', 'checkbox'], true) ? 'full' : null,
            ]);

        return $builtin->concat($custom)
            ->sortBy([['section', 'asc'], ['position', 'asc']])
            ->values()
            ->all();
    }

    public static function ensureSeeded(): void
    {
        if (FormSection::count() > 0) {
            return;
        }
        foreach (config('form.sections') as $i => $s) {
            FormSection::create([
                'key' => $s['key'],
                'label' => $s['label'],
                'note' => $s['note'] ?? null,
                'enabled' => true,
                'sort_order' => $i,
            ]);
        }
    }
}
