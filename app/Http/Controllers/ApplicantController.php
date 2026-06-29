<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\CustomField;
use App\Models\Program;
use App\Models\Setting;
use App\Support\BuiltinFields;
use App\Support\FormLayout;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantController extends Controller
{
    public function index(Request $request): Response
    {
        [$query, $filters, $customFilters, $filterCustom] = $this->filterApplicants($request);
        $listCustom = $this->enabledCustomFields()->where('show_in_list', true)->values();

        $applicants = $query
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Applicant $a) => [
                'id' => $a->id,
                'uli' => $a->uli,
                'name' => $a->display_name,
                'sex' => $a->sex,
                'age' => $a->age,
                'barangay' => $a->barangay,
                'contact' => $a->contact,
                'education' => $a->education,
                'program' => $a->program?->title,
                'level' => $a->program?->level,
                'status' => $a->status,
                'active' => $a->active,
                'photo_url' => $a->photo_url,
                'custom' => collect($listCustom)->mapWithKeys(fn ($cf) => [
                    $cf['key'] => data_get($a->custom_data, $cf['key']),
                ])->all(),
            ]);

        return Inertia::render('Applicants/Index', [
            'applicants' => $applicants,
            'filters' => array_merge($filters, ['custom' => $customFilters]),
            'options' => [
                'statuses' => config('lpf.statuses'),
                'programs' => Program::orderBy('title')->get(['id', 'title'])->all(),
                'barangays' => Applicant::query()->distinct()->orderBy('barangay')->pluck('barangay')->filter()->values(),
                'school_years' => Applicant::query()->distinct()->whereNotNull('school_year')->orderByDesc('school_year')->pluck('school_year')->values(),
                'class_sessions' => config('lpf.class_session'),
                'listCustom' => $listCustom,
                'filterCustom' => $filterCustom,
                'canExport' => $request->user()->can('export'),
            ],
        ]);
    }

    /** Shared filter pipeline for the list, CSV export, and PDF report. */
    private function filterApplicants(Request $request): array
    {
        $filters = $request->only(['search', 'status', 'program', 'barangay', 'active', 'school_year', 'class_session', 'registered_from', 'registered_to', 'sort', 'dir']);
        $filterCustom = $this->enabledCustomFields()->where('filterable', true)->values();

        $customFilters = [];
        foreach ($filterCustom as $cf) {
            $val = $request->input("cf_{$cf['key']}");
            if ($val !== null && $val !== '') {
                $customFilters[$cf['key']] = $val;
            }
        }

        $query = Applicant::query()
            ->with('program:id,title,level')
            ->when($filters['search'] ?? null, function ($q, $search) use ($filterCustom) {
                $q->where(function ($q) use ($search, $filterCustom) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('uli', 'like', "%{$search}%")
                        ->orWhere('contact', 'like', "%{$search}%");
                    foreach ($filterCustom as $cf) {
                        $q->orWhere("custom_data->{$cf['key']}", 'like', "%{$search}%");
                    }
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['program'] ?? null, fn ($q, $p) => $q->where('program_id', $p))
            ->when($filters['barangay'] ?? null, fn ($q, $b) => $q->where('barangay', $b))
            ->when($filters['school_year'] ?? null, fn ($q, $y) => $q->where('school_year', $y))
            ->when($filters['class_session'] ?? null, fn ($q, $cs) => $q->where('class_session', $cs))
            ->when($filters['registered_from'] ?? null, fn ($q, $d) => $q->whereDate('registered_at', '>=', $d))
            ->when($filters['registered_to'] ?? null, fn ($q, $d) => $q->whereDate('registered_at', '<=', $d))
            ->when(isset($filters['active']) && $filters['active'] !== '', function ($q) use ($filters) {
                $q->where('active', $filters['active'] === '1' || $filters['active'] === 'active');
            })
            ->where(function ($q) use ($customFilters) {
                foreach ($customFilters as $key => $val) {
                    $q->where("custom_data->{$key}", $val);
                }
            });

        // Click-to-sort columns (server-side, so it sorts the whole result set,
        // not just the current page). Defaults to newest registered first.
        $sortable = [
            'name' => 'last_name', 'uli' => 'uli', 'program' => 'program_id',
            'barangay' => 'barangay', 'status' => 'status', 'active' => 'active',
        ];
        $sort = $request->input('sort');
        $dir = $request->input('dir') === 'desc' ? 'desc' : 'asc';
        if ($sort && isset($sortable[$sort])) {
            $query->orderBy($sortable[$sort], $dir);
            if ($sort === 'name') {
                $query->orderBy('first_name', $dir);
            }
            $query->orderByDesc('id'); // stable tiebreaker
        } else {
            $query->orderByDesc('id');
        }

        return [$query, $filters, $customFilters, $filterCustom];
    }

    /** Human-readable summary of active filters for the report header. */
    private function filterSummary(array $filters, array $customFilters): array
    {
        $parts = [];
        if (! empty($filters['search'])) $parts[] = ['Search', $filters['search']];
        if (! empty($filters['status'])) $parts[] = ['Status', $filters['status']];
        if (! empty($filters['program'])) $parts[] = ['Program', Program::find($filters['program'])?->title ?? $filters['program']];
        if (! empty($filters['barangay'])) $parts[] = ['Barangay', $filters['barangay']];
        if (isset($filters['active']) && $filters['active'] !== '') {
            $parts[] = ['Records', in_array($filters['active'], ['inactive', '0'], true) ? 'Inactive only' : 'Active only'];
        }
        if (! empty($filters['school_year'])) $parts[] = ['School year', $filters['school_year']];
        if (! empty($filters['class_session'])) $parts[] = ['Class session', $filters['class_session']];
        if (! empty($filters['registered_from'])) $parts[] = ['Registered from', $filters['registered_from']];
        if (! empty($filters['registered_to'])) $parts[] = ['Registered to', $filters['registered_to']];
        foreach ($customFilters as $k => $v) $parts[] = [\Illuminate\Support\Str::headline($k), $v];

        return $parts;
    }

    /** Filtered CSV export (respects the on-screen filters). */
    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($request->user()->can('export'), 403);
        [$query] = $this->filterApplicants($request);
        $rows = $query->get();
        $custom = CustomField::where('enabled', true)->orderBy('section')->orderBy('sort_order')->get();

        $header = array_merge(
            ['ULI', 'Last name', 'First name', 'Sex', 'Age', 'Barangay', 'Contact', 'Education',
                'Program', 'Level', 'Class session', 'School year', 'Status', 'Active', 'Registered'],
            $custom->pluck('label')->all(),
        );

        $filename = 'applicants-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows, $custom, $header) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $a) {
                $line = [
                    $a->uli, $a->last_name, $a->first_name, $a->sex, $a->age, $a->barangay, $a->contact,
                    $a->education, $a->program?->title, $a->program?->level, $a->class_session, $a->school_year,
                    $a->status, $a->active ? 'Active' : 'Inactive', $a->registered_at?->toDateString(),
                ];
                foreach ($custom as $f) {
                    $v = data_get($a->custom_data, $f->key);
                    $line[] = is_bool($v) ? ($v ? 'Yes' : 'No') : $v;
                }
                fputcsv($out, $line);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Printable PDF report (respects the on-screen filters). */
    public function report(Request $request): \Illuminate\Contracts\View\View
    {
        abort_unless($request->user()->can('export'), 403);
        [$query, $filters, $customFilters] = $this->filterApplicants($request);
        $rows = $query->get();

        return view('applicants.report', [
            'rows' => $rows,
            'summary' => $this->filterSummary($filters, $customFilters),
            'count' => $rows->count(),
            'user' => $request->user(),
            'isAdmin' => $request->user()->hasRole('admin'),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('applicant.create'), 403);

        return Inertia::render('Applicants/Form', [
            'applicant' => null,
            'options' => $this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('applicant.create'), 403);

        $data = $this->validateApplicant($request);
        $data = $this->withDerived($data, $request);
        $data['custom_data'] = $this->customData($request);
        $data['uli'] = $this->nextUli();
        $data['status'] = 'Registered';
        $data['registered_at'] = now()->toDateString();

        $applicant = Applicant::create($data);

        return redirect()
            ->route('applicants.show', $applicant)
            ->with('success', "Applicant “{$applicant->display_name}” registered (ULI {$applicant->uli}).");
    }

    /** Printable TESDA Learner Profile Form (full record — restricted). */
    public function print(Request $request, Applicant $applicant): \Illuminate\Contracts\View\View
    {
        abort_unless($request->user()->can('pii.view'), 403);
        $applicant->load('program');

        return view('applicants.print', [
            'a' => $applicant,
            'program' => $applicant->program,
            'lpf' => config('lpf'),
            'customFields' => $this->enabledCustomFields(),
            'signatories' => Setting::signatories(),
        ]);
    }

    public function show(Request $request, Applicant $applicant): Response
    {
        $applicant->load('program', 'documents');
        $canPii = $request->user()->can('pii.view');

        return Inertia::render('Applicants/Show', [
            'applicant' => $canPii ? $this->fullPayload($applicant) : $this->limitedPayload($applicant),
            'pii' => $canPii,
            'documents' => $canPii ? $this->documentsPayload($applicant) : null,
            'canVerifyDocs' => $request->user()->can('docs.verify'),
            'customFields' => $canPii ? $this->enabledCustomFields() : null,
            'traineeStatuses' => config('lpf.trainee_statuses'),
        ]);
    }

    /** Build the per-requirement note checklist for the profile (pii roles only). */
    private function documentsPayload(Applicant $applicant): array
    {
        $byKey = $applicant->documents->keyBy('requirement_key');

        return collect(config('requirements'))->map(function ($req) use ($byKey) {
            $doc = $byKey->get($req['key']);

            return [
                'key' => $req['key'],
                'label' => $req['label'],
                'copies' => (int) ($req['copies'] ?? 1),
                'status' => $doc?->status ?? 'Pending',
                'note' => $doc?->note ?? '',
            ];
        })->all();
    }

    public function edit(Request $request, Applicant $applicant): Response
    {
        abort_unless($request->user()->can('applicant.edit'), 403);

        return Inertia::render('Applicants/Form', [
            'applicant' => $this->fullPayload($applicant),
            'options' => $this->formOptions(),
        ]);
    }

    public function update(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('applicant.edit'), 403);

        $data = $this->validateApplicant($request);
        $data = $this->withDerived($data, $request, $applicant);
        $data['custom_data'] = $this->customData($request);

        $applicant->update($data);

        return redirect()
            ->route('applicants.show', $applicant)
            ->with('success', "Applicant “{$applicant->display_name}” updated.");
    }

    public function destroy(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('applicant.delete'), 403);

        if ($applicant->photo_path) {
            Storage::disk('public')->delete($applicant->photo_path);
        }
        $name = $applicant->display_name;
        $applicant->delete();

        return redirect()->route('applicants.index')->with('success', "Applicant “{$name}” deleted.");
    }

    public function toggleActive(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('active'), 403);

        $applicant->update(['active' => ! $applicant->active]);

        return back()->with('success', $applicant->active
            ? "“{$applicant->display_name}” marked active."
            : "“{$applicant->display_name}” marked inactive.");
    }

    /** Set the trainee's training status (registrar / coordinator / manager / admin). */
    public function updateTraineeStatus(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('trainee.status'), 403);

        $data = $request->validate([
            'trainee_status' => ['nullable', Rule::in(config('lpf.trainee_statuses'))],
        ]);
        $applicant->update(['trainee_status' => $data['trainee_status'] ?? null]);

        return back()->with('success', $applicant->trainee_status
            ? "Training status set to {$applicant->trainee_status}."
            : 'Training status cleared.');
    }

    // ---------------------------------------------------------------- helpers

    private function validateApplicant(Request $request): array
    {
        $rules = [
            'last_name' => ['required', 'string', 'max:120'],
            'first_name' => ['required', 'string', 'max:120'],
            'middle_name' => ['nullable', 'string', 'max:120'],
            'ext_name' => ['nullable', 'string', 'max:20'],

            'street' => ['nullable', 'string', 'max:160'],
            'barangay' => ['required', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', Rule::in(config('lpf.regions'))],

            'email' => ['nullable', 'email', 'max:160'],
            'contact' => ['required', 'string', 'max:40'],
            'nationality' => ['nullable', 'string', 'max:80'],
            'religion' => ['nullable', 'string', 'max:80'],
            'ethnic_group' => ['nullable', 'string', 'max:120'],

            'employer_name' => ['nullable', 'string', 'max:160'],
            'employer_position' => ['nullable', 'string', 'max:120'],
            'school_last_attended' => ['nullable', 'string', 'max:200'],
            'year_graduated' => ['nullable', 'string', 'max:20'],
            'mother_maiden_name' => ['nullable', 'string', 'max:160'],

            'sss_no' => ['nullable', 'string', 'max:40'],
            'gsis_no' => ['nullable', 'string', 'max:40'],
            'tin_no' => ['nullable', 'string', 'max:40'],
            'philhealth_no' => ['nullable', 'string', 'max:40'],

            'sex' => ['required', Rule::in(config('lpf.sex'))],
            'civil_status' => ['nullable', Rule::in(config('lpf.civil_status'))],
            'emp_status' => ['nullable', Rule::in(config('lpf.emp_status'))],
            'emp_type' => ['nullable', Rule::in(config('lpf.emp_type'))],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'birthplace_city' => ['nullable', 'string', 'max:120'],
            'birthplace_province' => ['nullable', 'string', 'max:120'],
            'birthplace_region' => ['nullable', 'string', 'max:120'],
            'education' => ['nullable', Rule::in(config('lpf.education'))],

            'guardian_name' => ['nullable', 'string', 'max:160'],
            'guardian_address' => ['nullable', 'string', 'max:200'],

            'height' => ['nullable', 'string', 'max:40'],
            'weight' => ['nullable', 'string', 'max:40'],
            'blood_type' => ['nullable', Rule::in(config('lpf.blood_types'))],
            'eyesight' => ['nullable', Rule::in(config('lpf.rating'))],
            'hearing' => ['nullable', Rule::in(config('lpf.rating'))],
            'medical' => ['nullable', 'string', 'max:200'],

            'father_name' => ['nullable', 'string', 'max:160'],
            'father_occupation' => ['nullable', 'string', 'max:120'],
            'mother_name' => ['nullable', 'string', 'max:160'],
            'mother_occupation' => ['nullable', 'string', 'max:120'],
            'family_rank' => ['nullable', 'string', 'max:60'],
            'siblings' => ['nullable', 'string', 'max:40'],
            'spouse_name' => ['nullable', 'string', 'max:160'],
            'spouse_occupation' => ['nullable', 'string', 'max:120'],
            'children' => ['nullable', 'string', 'max:40'],

            'program_id' => ['required', 'exists:programs,id'],
            'scholarship' => ['nullable', Rule::in(config('lpf.scholarship'))],
            'class_session' => ['nullable', Rule::in(config('lpf.class_session'))],
            'school_year' => ['nullable', 'string', 'max:20'],

            'classifications' => ['nullable', 'array'],
            'classifications.*' => [Rule::in(config('lpf.classifications'))],
            'classification_other' => ['nullable', 'string', 'max:160'],
            'disability_type' => ['nullable', Rule::in(config('lpf.disability_types'))],
            'disability_cause' => ['nullable', Rule::in(config('lpf.disability_causes'))],

            'emergency_name' => ['nullable', 'string', 'max:160'],
            'emergency_relationship' => ['nullable', 'string', 'max:80'],
            'emergency_contact' => ['nullable', 'string', 'max:40'],
            'emergency_address' => ['nullable', 'string', 'max:200'],

            'education_history' => ['nullable', 'array'],
            'education_history.*.school' => ['nullable', 'string', 'max:160'],
            'education_history.*.started' => ['nullable', 'string', 'max:10'],
            'education_history.*.graduated' => ['nullable', 'string', 'max:10'],
            'education_history.*.status' => ['nullable', Rule::in(config('lpf.education_statuses'))],

            'privacy_consent' => ['boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],

            'photo' => ['nullable', 'image', 'max:4096'],

            // Verification (section 10)
            'date_accomplished' => ['nullable', 'date'],
            'date_received' => ['nullable', 'date'],
            'interviewed_by' => ['nullable', 'string', 'max:160'],
            'checked_by' => ['nullable', 'string', 'max:160'],
            'approved_by' => ['nullable', 'string', 'max:160'],
        ];

        $validated = $request->validate($this->applyFieldSettings($rules));

        if (array_key_exists('education_history', $validated)) {
            $validated['education_history'] = $this->cleanEducationHistory($validated['education_history']);
        }

        return $validated;
    }

    /** Keep only known levels with at least one value; normalize the row shape. */
    private function cleanEducationHistory(?array $history): ?array
    {
        $levels = collect(config('lpf.education_levels'))->pluck('key');
        $out = [];
        foreach ((array) $history as $key => $row) {
            if (! $levels->contains($key) || ! is_array($row)) {
                continue;
            }
            $clean = [
                'school' => trim((string) ($row['school'] ?? '')),
                'started' => trim((string) ($row['started'] ?? '')),
                'graduated' => trim((string) ($row['graduated'] ?? '')),
                'status' => $row['status'] ?? '',
            ];
            if ($clean['school'] !== '' || $clean['started'] !== '' || $clean['graduated'] !== '' || $clean['status'] !== '') {
                $out[$key] = $clean;
            }
        }

        return $out ?: null;
    }

    /** Compute derived fields (age from birthdate) + handle photo upload. */
    private function withDerived(array $data, Request $request, ?Applicant $applicant = null): array
    {
        if (! empty($data['birthdate'])) {
            $data['age'] = Carbon::parse($data['birthdate'])->age;
        }

        if ($request->hasFile('photo')) {
            if ($applicant?->photo_path) {
                Storage::disk('public')->delete($applicant->photo_path);
            }
            ImageOptimizer::uploaded($request->file('photo'), 800, 85); // 2×2 ID photo
            $data['photo_path'] = $request->file('photo')->store('applicant-photos', 'public');
        }
        unset($data['photo']);

        return $data;
    }

    private function nextUli(): string
    {
        $year = now()->format('y');
        $seq = Applicant::whereNotNull('uli')->count() + 1;

        do {
            $uli = sprintf('MPT-%s-%04d', $year, $seq);
            $seq++;
        } while (Applicant::where('uli', $uli)->exists());

        return $uli;
    }

    private function formOptions(): array
    {
        return [
            'programs' => Program::where('active', true)->orderBy('title')->get(['id', 'title', 'level'])->all(),
            'sex' => config('lpf.sex'),
            'civil_status' => config('lpf.civil_status'),
            'emp_status' => config('lpf.emp_status'),
            'emp_type' => config('lpf.emp_type'),
            'education' => config('lpf.education'),
            'regions' => config('lpf.regions'),
            'scholarship' => config('lpf.scholarship'),
            'class_session' => config('lpf.class_session'),
            'blood_types' => config('lpf.blood_types'),
            'rating' => config('lpf.rating'),
            'disability_types' => config('lpf.disability_types'),
            'disability_causes' => config('lpf.disability_causes'),
            'classifications' => config('lpf.classifications'),
            'education_levels' => config('lpf.education_levels'),
            'education_statuses' => config('lpf.education_statuses'),
            'signatories' => Setting::signatories(),
            // Data-driven layout: ordered categories + interleaved built-in/custom fields.
            'layout' => [
                'sections' => FormLayout::sections()
                    ->where('enabled', true)
                    ->map(fn ($s) => ['key' => $s->key, 'label' => $s->label, 'note' => $s->note])
                    ->values(),
                'fields' => FormLayout::formFields(),
            ],
        ];
    }

    /** Apply admin field settings (hidden → nullable, required toggle) to validation rules. */
    private function applyFieldSettings(array $rules): array
    {
        $settings = BuiltinFields::map();
        foreach ($rules as $key => $rule) {
            if (! isset($settings[$key]) || ! is_array($rule)) {
                continue;
            }
            $f = $settings[$key];
            // Strip existing required/nullable, then set per the effective setting.
            // A hidden or deleted field is never required (it isn't on the form).
            $rule = array_values(array_filter($rule, fn ($r) => ! in_array($r, ['required', 'nullable'], true)));
            array_unshift($rule, ($f['enabled'] && $f['required'] && ! $f['deleted']) ? 'required' : 'nullable');
            $rules[$key] = $rule;
        }

        return $rules;
    }

    private function enabledCustomFields()
    {
        return CustomField::where('enabled', true)
            ->orderBy('section')->orderBy('sort_order')
            ->get()
            ->map(fn (CustomField $f) => [
                'key' => $f->key,
                'label' => $f->label,
                'type' => $f->type,
                'options' => $f->options,
                'section' => $f->section,
                'required' => $f->required,
                'show_in_list' => $f->show_in_list,
                'filterable' => $f->filterable,
                'show_on_dashboard' => $f->show_on_dashboard,
            ]);
    }

    /** Validate + collect admin-defined custom field values into the custom_data array. */
    private function customData(Request $request): array
    {
        $fields = CustomField::where('enabled', true)->get();
        $rules = [];
        foreach ($fields as $f) {
            $key = "custom.{$f->key}";
            if ($f->type === 'checkbox') {
                $rules[$key] = [$f->required ? 'accepted' : 'boolean'];
            } else {
                $r = [$f->required ? 'required' : 'nullable'];
                if ($f->type === 'number') {
                    $r[] = 'numeric';
                } elseif ($f->type === 'date') {
                    $r[] = 'date';
                } else {
                    if ($f->type === 'select' && $f->options) $r[] = Rule::in($f->options);
                    $r[] = 'string';
                    $r[] = 'max:2000';
                }
                $rules[$key] = $r;
            }
        }
        $validated = $request->validate($rules);

        $out = [];
        foreach ($fields as $f) {
            $out[$f->key] = data_get($validated, "custom.{$f->key}");
        }

        return $out;
    }

    /** Full record (pii.view roles + edit). */
    private function fullPayload(Applicant $a): array
    {
        return array_merge($a->toArray(), [
            'display_name' => $a->display_name,
            'full_name' => $a->full_name,
            'photo_url' => $a->photo_url,
            'program' => $a->program ? [
                'id' => $a->program->id,
                'title' => $a->program->title,
                'level' => $a->program->level,
            ] : null,
        ]);
    }

    /** Redacted view for roles without pii.view (cashier / coordinator). */
    private function limitedPayload(Applicant $a): array
    {
        return [
            'id' => $a->id,
            'uli' => $a->uli,
            'display_name' => $a->display_name,
            'photo_url' => $a->photo_url,
            'active' => $a->active,
            'status' => $a->status,
            'trainee_status' => $a->trainee_status,
            'class_session' => $a->class_session,
            'school_year' => $a->school_year,
            'program' => $a->program ? [
                'title' => $a->program->title,
                'level' => $a->program->level,
            ] : null,
        ];
    }
}
