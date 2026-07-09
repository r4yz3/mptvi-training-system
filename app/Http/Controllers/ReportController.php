<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\CustomField;
use App\Models\Payment;
use App\Models\Program;
use App\Support\Xlsx;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(): InertiaResponse
    {
        $byStatus = Applicant::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $byProgram = Program::query()->withCount('applicants')->orderBy('title')->get()
            ->map(fn (Program $p) => ['program' => $p->title, 'applicants' => $p->applicants_count]);

        return Inertia::render('Reports/Index', [
            'byStatus' => $byStatus,
            'byProgram' => $byProgram,
            'totals' => [
                'applicants' => (int) Applicant::count(),
                'active' => (int) Applicant::where('active', true)->count(),
                'certified' => (int) Applicant::where('status', 'Certified')->count(),
            ],
            'programs' => Program::orderBy('title')->get(['id', 'title']),
            'statuses' => config('lpf.statuses'),
            'sessions' => config('lpf.class_session'),
            'barangays' => Applicant::query()->whereNotNull('barangay')->where('barangay', '!=', '')
                ->distinct()->orderBy('barangay')->pluck('barangay'),
            'schoolYears' => Applicant::query()->whereNotNull('school_year')->where('school_year', '!=', '')
                ->distinct()->orderBy('school_year', 'desc')->pluck('school_year'),
            'methods' => Payment::query()->whereNotNull('method')->distinct()->orderBy('method')->pluck('method'),
            'paymentTypes' => Payment::query()->whereNotNull('type')->distinct()->orderBy('type')->pluck('type'),
            'canFinance' => request()->user()->can('finance.view'),
        ]);
    }

    public function applicantsCsv(Request $request): Response
    {
        $full = $request->input('columns') === 'full';

        $rows = Applicant::query()
            ->with('program:id,title,level')
            ->when($full, fn (Builder $q) => $q->with('batch:id,code'))
            ->when($request->input('status'), fn (Builder $q, $s) => $q->where('status', $s))
            ->when($request->input('program'), fn (Builder $q, $p) => $q->where('program_id', $p))
            ->when($request->input('barangay'), fn (Builder $q, $b) => $q->where('barangay', $b))
            ->when($request->input('school_year'), fn (Builder $q, $y) => $q->where('school_year', $y))
            ->when($request->input('class_session'), fn (Builder $q, $c) => $q->where('class_session', $c))
            ->when($request->input('sex'), fn (Builder $q, $s) => $q->where('sex', $s))
            ->when($request->filled('active'), fn (Builder $q) => $q->where('active', $request->boolean('active')))
            ->when($request->input('registered_from'), fn (Builder $q, $d) => $q->whereDate('registered_at', '>=', $d))
            ->when($request->input('registered_to'), fn (Builder $q, $d) => $q->whereDate('registered_at', '<=', $d))
            ->orderBy('last_name')
            ->get();

        // All enabled custom fields become extra columns.
        $custom = CustomField::where('enabled', true)->orderBy('section')->orderBy('sort_order')->get();

        $cols = $full ? $this->fullApplicantColumns() : $this->summaryApplicantColumns();
        $header = array_merge(array_keys($cols), $custom->pluck('label')->all());

        $data = $rows->map(function (Applicant $a) use ($cols, $custom) {
            $line = array_map(fn (callable $fn) => $fn($a), array_values($cols));
            foreach ($custom as $f) {
                $v = data_get($a->custom_data, $f->key);
                $line[] = is_bool($v) ? ($v ? 'Yes' : 'No') : $v;
            }

            return $line;
        });

        return $this->output('applicants', $header, $data, $this->format($request));
    }

    public function paymentsCsv(Request $request): Response
    {
        abort_unless($request->user()->can('finance.view'), 403);

        $rows = Payment::query()->with(['applicant:id,first_name,last_name,program_id', 'cashier:id,name'])
            ->when($request->input('program'), fn (Builder $q, $p) => $q->whereHas('applicant', fn (Builder $a) => $a->where('program_id', $p)))
            ->when($request->input('method'), fn (Builder $q, $m) => $q->where('method', $m))
            ->when($request->input('type'), fn (Builder $q, $t) => $q->where('type', $t))
            ->when($request->input('status') === 'valid', fn (Builder $q) => $q->whereNull('voided_at'))
            ->when($request->input('status') === 'void', fn (Builder $q) => $q->whereNotNull('voided_at'))
            ->when($request->input('paid_from'), fn (Builder $q, $d) => $q->whereDate('paid_at', '>=', $d))
            ->when($request->input('paid_to'), fn (Builder $q, $d) => $q->whereDate('paid_at', '<=', $d))
            ->orderBy('paid_at')->get();

        $header = ['Date', 'OR No.', 'Learner', 'Category', 'Amount', 'Type', 'Method', 'Cashier', 'Status'];

        $data = $rows->map(fn (Payment $p) => [
            $this->dateStr($p->paid_at), $p->or_number, $p->applicant?->display_name, $p->category,
            $p->amount, $p->type, $p->method, $p->cashier?->name, $p->isVoided() ? 'VOID' : 'Valid',
        ]);

        return $this->output('payments', $header, $data, $this->format($request));
    }

    /** Requested output format: xlsx or csv (default). */
    private function format(Request $request): string
    {
        return $request->input('format') === 'xlsx' ? 'xlsx' : 'csv';
    }

    /** Stream a table as CSV or a real .xlsx, chosen by $format. */
    private function output(string $name, array $header, iterable $rows, string $format): Response
    {
        $stamp = now()->format('Ymd-His');

        if ($format === 'xlsx') {
            return Xlsx::download("{$name}-{$stamp}.xlsx", $header, $rows, ucfirst($name));
        }

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads accents correctly
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, "{$name}-{$stamp}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** The tidy default set of applicant columns. */
    private function summaryApplicantColumns(): array
    {
        return [
            'Last name' => fn (Applicant $a) => $a->last_name,
            'First name' => fn (Applicant $a) => $a->first_name,
            'Sex' => fn (Applicant $a) => $a->sex,
            'Age' => fn (Applicant $a) => $a->age,
            'Barangay' => fn (Applicant $a) => $a->barangay,
            'Contact' => fn (Applicant $a) => $a->contact,
            'Education' => fn (Applicant $a) => $a->education,
            'Program' => fn (Applicant $a) => $a->program?->title,
            'Level' => fn (Applicant $a) => $a->program?->level,
            'Class session' => fn (Applicant $a) => $a->class_session,
            'School year' => fn (Applicant $a) => $a->school_year,
            'Status' => fn (Applicant $a) => $a->status,
            'Active' => fn (Applicant $a) => $a->active ? 'Active' : 'Inactive',
            'Registered' => fn (Applicant $a) => $this->dateStr($a->registered_at),
        ];
    }

    /** Every meaningful field on the Learner Profile Form (the "complete" export). */
    private function fullApplicantColumns(): array
    {
        return [
            'Last name' => fn (Applicant $a) => $a->last_name,
            'First name' => fn (Applicant $a) => $a->first_name,
            'Middle name' => fn (Applicant $a) => $a->middle_name,
            'Ext.' => fn (Applicant $a) => $a->ext_name,
            'Sex' => fn (Applicant $a) => $a->sex,
            'Age' => fn (Applicant $a) => $a->age,
            'Birthdate' => fn (Applicant $a) => $this->dateStr($a->birthdate),
            'Birthplace city' => fn (Applicant $a) => $a->birthplace_city,
            'Birthplace province' => fn (Applicant $a) => $a->birthplace_province,
            'Birthplace region' => fn (Applicant $a) => $a->birthplace_region,
            'Civil status' => fn (Applicant $a) => $a->civil_status,
            'Nationality' => fn (Applicant $a) => $a->nationality,
            'Religion' => fn (Applicant $a) => $a->religion,
            'Blood type' => fn (Applicant $a) => $a->blood_type,
            'Contact' => fn (Applicant $a) => $a->contact,
            'Email' => fn (Applicant $a) => $a->email,
            'Street' => fn (Applicant $a) => $a->street,
            'Barangay' => fn (Applicant $a) => $a->barangay,
            'District' => fn (Applicant $a) => $a->district,
            'City' => fn (Applicant $a) => $a->city,
            'Province' => fn (Applicant $a) => $a->province,
            'Region' => fn (Applicant $a) => $a->region,
            'Registered voter' => fn (Applicant $a) => $a->voter ? 'Yes' : 'No',
            'SSS No.' => fn (Applicant $a) => $a->sss_no,
            'GSIS No.' => fn (Applicant $a) => $a->gsis_no,
            'TIN' => fn (Applicant $a) => $a->tin_no,
            'PhilHealth No.' => fn (Applicant $a) => $a->philhealth_no,
            'Education' => fn (Applicant $a) => $a->education,
            'School last attended' => fn (Applicant $a) => $a->school_last_attended,
            'Year graduated' => fn (Applicant $a) => $a->year_graduated,
            'Education history' => fn (Applicant $a) => $this->eduSummary($a),
            'Employment status' => fn (Applicant $a) => $a->emp_status,
            'Employment type' => fn (Applicant $a) => $a->emp_type,
            'Employer' => fn (Applicant $a) => $a->employer_name,
            'Position' => fn (Applicant $a) => $a->employer_position,
            'Father' => fn (Applicant $a) => $a->father_name,
            "Father's occupation" => fn (Applicant $a) => $a->father_occupation,
            'Mother' => fn (Applicant $a) => $a->mother_name,
            "Mother's maiden name" => fn (Applicant $a) => $a->mother_maiden_name,
            "Mother's occupation" => fn (Applicant $a) => $a->mother_occupation,
            'Spouse' => fn (Applicant $a) => $a->spouse_name,
            "Spouse's occupation" => fn (Applicant $a) => $a->spouse_occupation,
            'Family rank' => fn (Applicant $a) => $a->family_rank,
            'Siblings' => fn (Applicant $a) => $a->siblings,
            'Children' => fn (Applicant $a) => $a->children,
            'Guardian' => fn (Applicant $a) => $a->guardian_name,
            'Guardian address' => fn (Applicant $a) => $a->guardian_address,
            'Emergency contact' => fn (Applicant $a) => $a->emergency_name,
            'Emergency relationship' => fn (Applicant $a) => $a->emergency_relationship,
            'Emergency phone' => fn (Applicant $a) => $a->emergency_contact,
            'Emergency address' => fn (Applicant $a) => $a->emergency_address,
            'Classifications' => fn (Applicant $a) => is_array($a->classifications) ? implode(', ', $a->classifications) : '',
            'Classification (other)' => fn (Applicant $a) => $a->classification_other,
            'Disability type' => fn (Applicant $a) => $a->disability_type,
            'Disability cause' => fn (Applicant $a) => $a->disability_cause,
            'Ethnic group' => fn (Applicant $a) => $a->ethnic_group,
            'Scholarship' => fn (Applicant $a) => $a->scholarship,
            'Height' => fn (Applicant $a) => $a->height,
            'Weight' => fn (Applicant $a) => $a->weight,
            'Eyesight' => fn (Applicant $a) => $a->eyesight,
            'Hearing' => fn (Applicant $a) => $a->hearing,
            'Medical' => fn (Applicant $a) => $a->medical,
            'Program' => fn (Applicant $a) => $a->program?->title,
            'Level' => fn (Applicant $a) => $a->program?->level,
            'Batch' => fn (Applicant $a) => $a->batch?->code,
            'Class session' => fn (Applicant $a) => $a->class_session,
            'School year' => fn (Applicant $a) => $a->school_year,
            'Status' => fn (Applicant $a) => $a->status,
            'Trainee status' => fn (Applicant $a) => $a->trainee_status,
            'Active' => fn (Applicant $a) => $a->active ? 'Active' : 'Inactive',
            'Registered' => fn (Applicant $a) => $this->dateStr($a->registered_at),
            'Screened' => fn (Applicant $a) => $this->dateStr($a->screened_at),
            'Certified' => fn (Applicant $a) => $this->dateStr($a->certified_at),
            'Cert number' => fn (Applicant $a) => $a->cert_number,
            'ID issued' => fn (Applicant $a) => $this->dateStr($a->id_issued_at),
            'Privacy consent' => fn (Applicant $a) => $a->privacy_consent ? 'Yes' : 'No',
            'Remarks' => fn (Applicant $a) => $a->remarks,
        ];
    }

    /** One readable cell summarizing the education-history grid. */
    private function eduSummary(Applicant $a): string
    {
        $hist = $a->education_history;
        if (! is_array($hist) || $hist === []) {
            return '';
        }

        $labels = collect(config('lpf.education_levels'))->pluck('label', 'key');
        $parts = [];
        foreach ($hist as $key => $row) {
            if (! is_array($row)) {
                continue;
            }
            $school = trim((string) ($row['school'] ?? ''));
            $detail = array_filter([
                trim((string) ($row['status'] ?? '')),
                trim((string) ($row['year_graduated'] ?? $row['year'] ?? '')),
            ]);
            if ($school === '' && $detail === []) {
                continue;
            }
            $parts[] = ($labels[$key] ?? $key) . ': ' . $school . ($detail ? ' (' . implode(', ', $detail) . ')' : '');
        }

        return implode(' | ', $parts);
    }

    /** Format a date column that may be a Carbon instance or a raw string. */
    private function dateStr($value): string
    {
        if (empty($value)) {
            return '';
        }
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->toDateString();
        }

        return (string) Str::of((string) $value)->substr(0, 10);
    }
}
