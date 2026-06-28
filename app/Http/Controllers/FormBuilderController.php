<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\FieldSetting;
use App\Models\FormSection;
use App\Support\BuiltinFields;
use App\Support\FormLayout;
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

        $sections = FormLayout::sections();

        return Inertia::render('FormBuilder/Index', [
            'sections' => $sections,
            'fields' => CustomField::orderBy('section')->orderBy('position')->orderBy('sort_order')->get(),
            'builtinFields' => BuiltinFields::all(),
            'sectionOptions' => $sections->map(fn ($s) => ['value' => $s->key, 'label' => $s->label]),
            'fieldTypes' => config('form.field_types'),
        ]);
    }

    // ----------------------------------------------------------- categories

    public function storeSection(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);
        $data = $request->validate(['label' => ['required', 'string', 'max:120']]);

        FormSection::create([
            'key' => $this->uniqueSectionKey($data['label']),
            'label' => $data['label'],
            'enabled' => true,
            'sort_order' => (FormSection::max('sort_order') ?? -1) + 1,
        ]);

        return back()->with('success', "Category “{$data['label']}” added.");
    }

    public function updateSection(Request $request, FormSection $section): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:300'],
            'enabled' => ['boolean'],
        ]);
        $section->update([
            'label' => $data['label'],
            'note' => $data['note'] ?? null,
            'enabled' => (bool) ($data['enabled'] ?? $section->enabled),
        ]);

        return back()->with('success', "Category “{$section->label}” updated.");
    }

    public function toggleSection(Request $request, FormSection $section): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);
        $section->update(['enabled' => ! $section->enabled]);

        return back()->with('success', "Category “{$section->label}” " . ($section->enabled ? 'shown.' : 'hidden.'));
    }

    public function destroySection(Request $request, FormSection $section): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $fallback = FormSection::where('id', '!=', $section->id)->orderBy('sort_order')->first();
        abort_if($fallback === null, 422, 'At least one category is required.');

        // Move any fields out of this category so nothing is orphaned.
        foreach (BuiltinFields::all() as $f) {
            if ($f['section'] === $section->key) {
                FieldSetting::updateOrCreate(['key' => $f['key']], ['section' => $fallback->key]);
            }
        }
        CustomField::where('section', $section->key)->update(['section' => $fallback->key]);

        $label = $section->label;
        $section->delete();

        return back()->with('success', "Category “{$label}” deleted. Its fields moved to “{$fallback->label}”.");
    }

    public function reorderSections(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);
        $data = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);
        foreach ($data['order'] as $i => $id) {
            FormSection::where('id', $id)->update(['sort_order' => $i]);
        }

        return back();
    }

    // ------------------------------------------------------- built-in fields

    /** Relabel / show-hide / require / move a built-in field (locked: relabel + move only). */
    public function updateBuiltin(Request $request, string $key): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $registry = collect(config('builtin_fields.fields'))->firstWhere('key', $key);
        abort_if($registry === null, 404);
        $locked = $registry['locked'] ?? false;

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'section' => ['nullable', Rule::in(FormSection::pluck('key'))],
            'enabled' => ['boolean'],
            'required' => ['boolean'],
        ]);

        FieldSetting::updateOrCreate(['key' => $key], [
            // a blank label override falls back to the registry default
            'label' => ($data['label'] ?? '') !== '' ? $data['label'] : null,
            'section' => $data['section'] ?? $registry['section'],
            'enabled' => $locked ? true : (bool) ($data['enabled'] ?? true),
            'required' => $locked ? true : (bool) ($data['required'] ?? false),
            'deleted' => false,
        ]);

        return back()->with('success', "Field “{$registry['label']}” updated.");
    }

    /** Soft-delete a built-in field (it disappears from the form; locked fields can't be deleted). */
    public function destroyBuiltin(Request $request, string $key): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $registry = collect(config('builtin_fields.fields'))->firstWhere('key', $key);
        abort_if($registry === null, 404);
        abort_if($registry['locked'] ?? false, 422, 'System-critical fields cannot be deleted.');

        FieldSetting::updateOrCreate(['key' => $key], ['deleted' => true]);

        return back()->with('success', "Field “{$registry['label']}” removed.");
    }

    public function restoreBuiltin(Request $request, string $key): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);
        $registry = collect(config('builtin_fields.fields'))->firstWhere('key', $key);
        abort_if($registry === null, 404);

        FieldSetting::updateOrCreate(['key' => $key], ['deleted' => false, 'enabled' => true]);

        return back()->with('success', "Field “{$registry['label']}” restored.");
    }

    // -------------------------------------------------------- custom fields

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

    // -------------------------------------------------------- layout (DnD)

    /**
     * Persist a drag-and-drop layout change: each item names a field, its new
     * category and order. Built-ins write to field_settings, customs to custom_fields.
     */
    public function reorderLayout(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.kind' => ['required', Rule::in(['builtin', 'custom'])],
            'items.*.key' => ['required', 'string'],
            'items.*.section' => ['required', Rule::in(FormSection::pluck('key'))],
            'items.*.position' => ['required', 'integer'],
        ]);

        $builtinKeys = collect(config('builtin_fields.fields'))->pluck('key');

        foreach ($data['items'] as $item) {
            if ($item['kind'] === 'builtin') {
                if (! $builtinKeys->contains($item['key'])) {
                    continue;
                }
                FieldSetting::updateOrCreate(['key' => $item['key']], [
                    'section' => $item['section'],
                    'position' => $item['position'],
                ]);
            } else {
                CustomField::where('key', $item['key'])->update([
                    'section' => $item['section'],
                    'position' => $item['position'],
                ]);
            }
        }

        return back()->with('success', 'Form layout saved.');
    }

    // ---- helpers ----

    private function validateField(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(config('form.field_types'))],
            'section' => ['required', Rule::in(FormSection::pluck('key'))],
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

    private function uniqueSectionKey(string $label): string
    {
        $base = 'sec-' . (Str::slug($label) ?: 'category');
        $key = $base;
        $i = 1;
        while (FormSection::where('key', $key)->exists()) {
            $key = $base . '-' . (++$i);
        }

        return $key;
    }
}
