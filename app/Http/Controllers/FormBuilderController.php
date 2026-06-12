<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\FieldSetting;
use App\Models\FormSection;
use App\Support\BuiltinFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FormBuilderController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        $this->ensureSectionsSeeded();

        return Inertia::render('FormBuilder/Index', [
            'sections' => FormSection::orderBy('sort_order')->get(['id', 'key', 'label', 'enabled']),
            'fields' => CustomField::orderBy('section')->orderBy('sort_order')->get(),
            'builtinFields' => BuiltinFields::all(),
            'sectionOptions' => collect(config('form.sections'))->map(fn ($s) => ['value' => $s['key'], 'label' => $s['label']]),
            'fieldTypes' => config('form.field_types'),
        ]);
    }

    public function toggleSection(Request $request, FormSection $section): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);
        $section->update(['enabled' => ! $section->enabled]);

        return back()->with('success', "Section “{$section->label}” " . ($section->enabled ? 'shown.' : 'hidden.'));
    }

    /** Update a built-in field's label / visibility / required (locked fields: label only). */
    public function updateBuiltin(Request $request, string $key): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $registry = collect(config('builtin_fields.fields'))->firstWhere('key', $key);
        abort_if($registry === null, 404);
        $locked = $registry['locked'] ?? false;

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'enabled' => ['boolean'],
            'required' => ['boolean'],
        ]);

        FieldSetting::updateOrCreate(['key' => $key], [
            // a blank label override falls back to the registry default
            'label' => ($data['label'] ?? '') !== '' ? $data['label'] : null,
            'enabled' => $locked ? true : (bool) ($data['enabled'] ?? true),
            'required' => $locked ? true : (bool) ($data['required'] ?? false),
        ]);

        return back()->with('success', "Field “{$registry['label']}” updated.");
    }

    public function storeField(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);
        $data = $this->validateField($request);
        $data['key'] = $this->uniqueKey($data['label']);
        $data['options'] = $this->splitOptions($request->input('options_text'), $data['type']);
        $data['sort_order'] = (CustomField::where('section', $data['section'])->max('sort_order') ?? -1) + 1;

        CustomField::create($data);

        return back()->with('success', "Custom field “{$data['label']}” added.");
    }

    public function updateField(Request $request, CustomField $field): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);
        $data = $this->validateField($request);
        $data['options'] = $this->splitOptions($request->input('options_text'), $data['type']);
        // Key is stable once created (it's the JSON storage key) — don't rename.
        $field->update($data);

        return back()->with('success', "Custom field “{$field->label}” updated.");
    }

    public function destroyField(Request $request, CustomField $field): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);
        $label = $field->label;
        $field->delete();

        return back()->with('success', "Custom field “{$label}” deleted.");
    }

    // ---- helpers ----

    private function validateField(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(config('form.field_types'))],
            'section' => ['required', Rule::in(collect(config('form.sections'))->pluck('key'))],
            'required' => ['boolean'],
            'enabled' => ['boolean'],
            'show_in_list' => ['boolean'],
            'filterable' => ['boolean'],
            'show_on_dashboard' => ['boolean'],
        ]);
    }

    private function splitOptions(?string $text, string $type): ?array
    {
        if ($type !== 'select' || ! $text) {
            return null;
        }

        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($x) => trim($x))->filter()->values()->all();
    }

    private function uniqueKey(string $label): string
    {
        $base = Str::slug($label, '_') ?: 'field';
        $key = $base;
        $i = 1;
        while (CustomField::where('key', $key)->exists()) {
            $key = $base . '_' . (++$i);
        }

        return $key;
    }

    private function ensureSectionsSeeded(): void
    {
        if (FormSection::count() === 0) {
            foreach (config('form.sections') as $i => $s) {
                FormSection::create(['key' => $s['key'], 'label' => $s['label'], 'enabled' => true, 'sort_order' => $i]);
            }
        }
    }
}
