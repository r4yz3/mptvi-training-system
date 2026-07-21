<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\CustomField;
use App\Models\FeeItem;
use App\Models\FormSection;
use App\Models\Program;
use App\Models\SecurityEvent;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Support\SecurityPosture;
use App\Support\SystemHealth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        $sig = Setting::signatories();

        return Inertia::render('Settings/Index', [
            'stats' => [
                'customFields' => CustomField::count(),
                'hiddenSections' => FormSection::where('enabled', false)->count(),
                'users' => User::count(),
                'programs' => \App\Models\Program::count(),
                'applicants' => Applicant::count(),
                'assessor' => Setting::assessor(),
                'institution' => Setting::institution()['name'],
                'checkedBy' => $sig['checked_by']['name'],
                'roles' => count(config('rbac.roles')),
            ],
            'system' => [
                'app' => config('app.name'),
                'env' => app()->environment(),
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
            ],
        ]);
    }

    /** Live system health + hardware advice (fetched async by the Settings panel). */
    public function health(Request $request, SystemHealth $health): JsonResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        return response()->json($health->report());
    }

    /** Security posture + recent authentication activity. */
    public function security(Request $request, SecurityPosture $posture): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        $events = SecurityEvent::with('user:id,name')->latest('id')->limit(60)->get()->map(fn (SecurityEvent $e) => [
            'id' => $e->id,
            'type' => $e->type,
            'email' => $e->email,
            'user' => $e->user?->name,
            'ip' => $e->ip,
            'at' => $e->created_at?->diffForHumans(),
            'at_full' => $e->created_at?->toDayDateTimeString(),
        ]);

        return Inertia::render('Settings/Security', [
            'posture' => $posture->report(),
            'events' => $events,
        ]);
    }

    public function signatories(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        $sig = Setting::signatories();

        return Inertia::render('Settings/Signatories', [
            'values' => [
                'assessor' => Setting::assessor(),
                'checked_name' => $sig['checked_by']['name'],
                'checked_title' => $sig['checked_by']['title'],
                'approved_name' => $sig['approved_by']['name'],
                'approved_title' => $sig['approved_by']['title'],
            ],
        ]);
    }

    public function updateSignatories(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $data = $request->validate([
            'assessor' => ['nullable', 'string', 'max:160'],
            'checked_name' => ['nullable', 'string', 'max:160'],
            'checked_title' => ['nullable', 'string', 'max:160'],
            'approved_name' => ['nullable', 'string', 'max:160'],
            'approved_title' => ['nullable', 'string', 'max:160'],
        ]);

        Setting::put('assessor', $data['assessor'] ?? '');
        Setting::put('signatory_checked_name', $data['checked_name'] ?? '');
        Setting::put('signatory_checked_title', $data['checked_title'] ?? '');
        Setting::put('signatory_approved_name', $data['approved_name'] ?? '');
        Setting::put('signatory_approved_title', $data['approved_title'] ?? '');

        return back()->with('success', 'Signatories & certificate settings saved.');
    }

    public function institution(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        return Inertia::render('Settings/Institution', [
            'values' => Setting::institution(),
        ]);
    }

    public function updateInstitution(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:200'],
            'short_name' => ['nullable', 'string', 'max:40'],
            'office' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:200'],
            'contact' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'string', 'email', 'max:120'],
        ]);

        foreach (['name', 'short_name', 'office', 'address', 'contact', 'email'] as $k) {
            Setting::put("org_{$k}", $data[$k] ?? '');
        }

        return back()->with('success', 'Institution profile saved.');
    }

    /* ----------------------------- Document requirements ----------------------------- */

    public function requirements(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        return Inertia::render('Settings/Requirements', [
            'requirements' => collect(config('requirements'))->map(fn ($r) => [
                'key' => $r['key'],
                'label' => $r['label'],
                'copies' => (int) ($r['copies'] ?? 1),
                'enabled' => (bool) ($r['enabled'] ?? true),
            ])->values(),
        ]);
    }

    public function updateRequirements(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $data = $request->validate([
            'requirements' => ['present', 'array'],
            'requirements.*.key' => ['required', 'integer'],
            'requirements.*.label' => ['required', 'string', 'max:200'],
            'requirements.*.copies' => ['integer', 'min:1', 'max:20'],
            'requirements.*.enabled' => ['boolean'],
        ]);

        // Preserve stable integer keys; assign new ones to added rows.
        $maxKey = collect($data['requirements'])->max('key') ?? -1;
        $items = collect($data['requirements'])->map(function ($r) use (&$maxKey) {
            $key = $r['key'] >= 0 ? $r['key'] : ++$maxKey;

            return [
                'key' => $key,
                'label' => $r['label'],
                'copies' => max(1, (int) ($r['copies'] ?? 1)),
                'enabled' => (bool) ($r['enabled'] ?? true),
            ];
        })->values()->all();

        Setting::putJson('requirements', $items);

        return back()->with('success', 'Documentary requirements saved.');
    }

    /* ----------------------------- Education grid ----------------------------- */

    public function education(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        return Inertia::render('Settings/Education', [
            'levels' => collect(config('lpf.education_levels'))->map(fn ($l) => [
                'key' => $l['key'],
                'label' => $l['label'],
            ])->values(),
            'statuses' => array_values(config('lpf.education_statuses', [])),
        ]);
    }

    public function updateEducation(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $data = $request->validate([
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.key' => ['nullable', 'string', 'max:60'],
            'levels.*.label' => ['required', 'string', 'max:80'],
            'statuses' => ['required', 'array', 'min:1'],
            'statuses.*' => ['required', 'string', 'max:60'],
        ]);

        // Keys tie a row to the data already saved on applicants — keep existing
        // ones untouched and derive a unique slug for rows added in the UI.
        $used = [];
        $levels = collect($data['levels'])->map(function ($l) use (&$used) {
            $key = trim((string) ($l['key'] ?? ''));
            if ($key === '') {
                $key = \Illuminate\Support\Str::slug($l['label'], '_') ?: 'level';
            }
            $base = $key;
            for ($i = 2; in_array($key, $used, true); $i++) {
                $key = "{$base}_{$i}";
            }
            $used[] = $key;

            return ['key' => $key, 'label' => trim($l['label'])];
        })->values()->all();

        $statuses = collect($data['statuses'])->map(fn ($s) => trim($s))->filter()->unique()->values()->all();

        Setting::putJson('lpf_education_levels', $levels);
        Setting::putJson('lpf_education_statuses', $statuses);

        return back()->with('success', 'Educational attainment grid saved.');
    }

    /* ------------------------- Competency standards (TESDA) ------------------------- */

    public function subjects(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        $programs = Program::with('subjects')->orderBy('title')->get()
            ->map(fn (Program $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'level' => $p->level,
                'subjects' => $p->subjects->map(fn (Subject $s) => [
                    'id' => $s->id, 'code' => $s->code, 'title' => $s->title, 'category' => $s->category, 'units' => $s->units,
                ])->values(),
            ]);

        return Inertia::render('Settings/Subjects', [
            'programs' => $programs,
            'categories' => Subject::CATEGORIES,
        ]);
    }

    /** Replace one program's subjects. Rows with an id are kept (so existing
     *  grades survive); rows omitted are deleted (cascading grades). */
    public function updateSubjects(Request $request, Program $program): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $data = $request->validate([
            'subjects' => ['present', 'array'],
            'subjects.*.id' => ['nullable', 'integer'],
            'subjects.*.code' => ['nullable', 'string', 'max:60'],
            'subjects.*.title' => ['required', 'string', 'max:160'],
            'subjects.*.category' => ['required', Rule::in(Subject::CATEGORIES)],
            'subjects.*.units' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $keptIds = [];
        foreach (array_values($data['subjects']) as $sort => $row) {
            $subject = $program->subjects()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'code' => trim((string) ($row['code'] ?? '')) ?: null,
                    'title' => trim($row['title']),
                    'category' => $row['category'],
                    'units' => (int) $row['units'],
                    'sort' => $sort,
                ],
            );
            $keptIds[] = $subject->id;
        }

        // Remove subjects the admin deleted (their grades cascade away).
        $program->subjects()->whereNotIn('id', $keptIds)->delete();

        return back()->with('success', "Subjects saved for {$program->title}.");
    }

    /* --------------------------- Extra fee schedule ---------------------------- */

    /** Per-program, per-school-year amounts for the trackable extra fees. */
    public function fees(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        $categories = config('lpf.scheduled_fee_categories');

        $years = Applicant::query()->distinct()->whereNotNull('school_year')->pluck('school_year')
            ->merge(FeeItem::query()->distinct()->pluck('school_year'))
            ->push((string) config('academic.school_year', ''))
            ->filter()->unique()->sortDesc()->values();

        $year = trim((string) $request->query('year', ''));
        if ($year === '') {
            $year = (string) ($years->first() ?? config('academic.school_year', ''));
        }

        $lookup = FeeItem::where('school_year', $year)->get()
            ->groupBy('program_id')
            ->map(fn ($rows) => $rows->pluck('amount', 'category'));

        $programs = Program::orderBy('title')->get(['id', 'title', 'level', 'fee'])
            ->map(fn (Program $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'level' => $p->level,
                'misc_fee' => (int) $p->fee,
                'amounts' => collect($categories)
                    ->mapWithKeys(fn ($c) => [$c => (int) ($lookup[$p->id][$c] ?? 0)]),
            ]);

        return Inertia::render('Settings/Fees', [
            'categories' => $categories,
            'miscFeeCategory' => config('lpf.training_fee_category'),
            'years' => $years,
            'year' => $year,
            'programs' => $programs,
        ]);
    }

    /** Save the extra-fee amounts for one school year (upsert per program+category). */
    public function updateFees(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $data = $request->validate([
            'school_year' => ['required', 'string', 'max:20'],
            'amounts' => ['present', 'array'],           // amounts[program_id][category] = pesos
            'amounts.*' => ['array'],
            'amounts.*.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        $categories = config('lpf.scheduled_fee_categories');
        $programIds = Program::pluck('id')->flip();

        foreach ($data['amounts'] as $programId => $cats) {
            if (! $programIds->has((int) $programId)) {
                continue;
            }
            foreach ($categories as $cat) {
                FeeItem::updateOrCreate(
                    ['program_id' => (int) $programId, 'school_year' => $data['school_year'], 'category' => $cat],
                    ['amount' => max(0, (int) ($cats[$cat] ?? 0))],
                );
            }
        }

        return back()->with('success', "Fees saved for {$data['school_year']}.");
    }

    /* ----------------------------- Reference lists ----------------------------- */

    public function lists(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        $labels = [
            'civil_status' => 'Civil status', 'emp_status' => 'Employment status', 'emp_type' => 'Employment type',
            'education' => 'Educational attainment', 'scholarship' => 'Scholarship / program', 'classifications' => 'Learner classifications',
            'blood_types' => 'Blood types', 'regions' => 'Regions',
        ];

        return Inertia::render('Settings/Lists', [
            'lists' => collect(Setting::EDITABLE_LISTS)->map(fn ($key) => [
                'key' => $key,
                'label' => $labels[$key] ?? $key,
                'items' => array_values(config("lpf.{$key}", [])),
            ])->values(),
        ]);
    }

    public function updateLists(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $key = $request->input('key');
        abort_unless(in_array($key, Setting::EDITABLE_LISTS, true), 422);

        $data = $request->validate([
            'items' => ['present', 'array'],
            'items.*' => ['required', 'string', 'max:200'],
        ]);

        $items = collect($data['items'])->map(fn ($v) => trim($v))->filter()->unique()->values()->all();
        Setting::putJson("lpf_{$key}", $items);

        return back()->with('success', 'List saved.');
    }

    /* ----------------------------- Academic defaults ----------------------------- */

    public function academic(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        return Inertia::render('Settings/Academic', [
            'values' => [
                'school_year' => (string) config('academic.school_year', ''),
                'default_session' => (string) config('academic.default_session', ''),
                'default_fee' => (int) config('academic.default_fee', 0),
                'age_min' => (int) config('academic.age_min', 15),
                'age_max' => (int) config('academic.age_max', 60),
                'cert_prefix' => (string) config('academic.cert_prefix', 'CK2'),
            ],
            'sessions' => config('lpf.class_session'),
        ]);
    }

    public function updateAcademic(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $data = $request->validate([
            'school_year' => ['nullable', 'string', 'max:20'],
            'default_session' => ['nullable', 'string', 'max:20'],
            'default_fee' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'age_min' => ['required', 'integer', 'min:1', 'max:120'],
            'age_max' => ['required', 'integer', 'min:1', 'max:120', 'gte:age_min'],
            'cert_prefix' => ['required', 'string', 'max:12'],
        ]);

        Setting::put('acad_school_year', $data['school_year'] ?? '');
        Setting::put('acad_default_session', $data['default_session'] ?? '');
        Setting::put('acad_default_fee', (string) ($data['default_fee'] ?? 0));
        Setting::put('acad_age_min', (string) $data['age_min']);
        Setting::put('acad_age_max', (string) $data['age_max']);
        Setting::put('cert_prefix', strtoupper(trim($data['cert_prefix'])));

        return back()->with('success', 'Academic defaults saved.');
    }

    /* ----------------------------- Branding & logos ----------------------------- */

    public function branding(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        return Inertia::render('Settings/Branding', [
            'color' => Setting::brandColor(),
            'version' => (string) Setting::get('logo_version', ''),
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings'), 403);

        $data = $request->validate([
            'color' => ['nullable', 'regex:/^#?[0-9a-fA-F]{6}$/'],
            'mptvi_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'magsaysay_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        Setting::put('brand_color', $data['color'] ? '#' . ltrim($data['color'], '#') : '');

        // Logos overwrite the public files referenced by the ID, certificate and shell.
        foreach (['mptvi_logo' => 'mptvi-logo.png', 'magsaysay_logo' => 'magsaysay-logo.png'] as $field => $target) {
            if ($request->hasFile($field)) {
                $request->file($field)->move(public_path(), $target);
                \App\Support\ImageOptimizer::file(public_path($target), 512); // logos display small
            }
        }
        if ($request->hasFile('mptvi_logo') || $request->hasFile('magsaysay_logo')) {
            Setting::put('logo_version', (string) now()->timestamp);
        }

        return back()->with('success', 'Branding saved.');
    }

    /** Read-only role → capability + module access matrix (from config/rbac.php). */
    public function access(Request $request): Response
    {
        abort_unless($request->user()->can('settings'), 403);

        $rbac = config('rbac');

        return Inertia::render('Settings/Access', [
            'roles' => collect($rbac['roles'])->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'capabilities' => $rbac['capabilities'],
            'matrix' => $rbac['matrix'],
            'modules' => collect($rbac['modules'])->map(fn ($m) => [
                'label' => $m['label'], 'group' => $m['group'], 'roles' => $m['roles'],
            ])->values(),
        ]);
    }
}
