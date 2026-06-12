<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Payment;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): Response
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

    public function applicantsCsv(Request $request): StreamedResponse
    {
        $rows = Applicant::query()
            ->with('program:id,title,level')
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
        $custom = \App\Models\CustomField::where('enabled', true)->orderBy('section')->orderBy('sort_order')->get();

        $header = array_merge(
            ['ULI', 'Last name', 'First name', 'Sex', 'Age', 'Barangay', 'Contact',
                'Education', 'Program', 'Level', 'Class session', 'School year', 'Status', 'Active', 'Registered'],
            $custom->pluck('label')->all(),
        );

        return $this->stream('applicants', $header, $rows->map(function (Applicant $a) use ($custom) {
            $base = [
                $a->uli, $a->last_name, $a->first_name, $a->sex, $a->age, $a->barangay, $a->contact,
                $a->education, $a->program?->title, $a->program?->level,
                $a->class_session, $a->school_year, $a->status, $a->active ? 'Active' : 'Inactive',
                $a->registered_at?->toDateString(),
            ];
            foreach ($custom as $f) {
                $v = data_get($a->custom_data, $f->key);
                $base[] = is_bool($v) ? ($v ? 'Yes' : 'No') : $v;
            }

            return $base;
        }));
    }

    public function paymentsCsv(Request $request): StreamedResponse
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

        $header = ['Date', 'OR No.', 'Learner', 'Amount', 'Type', 'Method', 'Cashier', 'Status'];

        return $this->stream('payments', $header, $rows->map(fn (Payment $p) => [
            $p->paid_at?->toDateString(), $p->or_number, $p->applicant?->display_name,
            $p->amount, $p->type, $p->method, $p->cashier?->name, $p->isVoided() ? 'VOID' : 'Valid',
        ]));
    }

    private function stream(string $name, array $header, $rows): StreamedResponse
    {
        $filename = "{$name}-" . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
