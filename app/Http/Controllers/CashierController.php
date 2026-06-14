<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Payment;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CashierController extends Controller
{
    public function index(Request $request): Response
    {
        $canFinance = $request->user()->can('finance.view');

        // Worklist — learners who still owe (fee > 0, balance > 0), not disqualified.
        $worklist = Applicant::query()
            ->with('program:id,title,fee')
            ->where('active', true)
            ->where('status', '!=', 'Disqualified')
            ->get()
            ->filter(fn (Applicant $a) => $a->fee() > 0 && $a->balance() > 0)
            ->map(fn (Applicant $a) => [
                'id' => $a->id,
                'name' => $a->display_name,
                'program' => $a->program?->title,
                'fee' => $a->fee(),
                'paid' => $a->paidTotal(),
                'balance' => $a->balance(),
                'pay_status' => $a->payStatus(),
                'status' => $a->status,
            ])
            ->sortByDesc('balance')
            ->values();

        // Ledger — admin sees all; a cashier sees only their own entries.
        $ledger = Payment::query()
            ->with(['applicant:id,first_name,last_name', 'cashier:id,name'])
            ->when(! $canFinance, fn ($q) => $q->where('cashier_id', $request->user()->id))
            ->latest('paid_at')->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (Payment $p) => [
                'id' => $p->id,
                'applicant' => $p->applicant?->display_name,
                'amount' => $p->amount,
                'type' => $p->type,
                'method' => $p->method,
                'or_number' => $p->or_number,
                'paid_at' => $p->paid_at?->toDateString(),
                'cashier' => $p->cashier?->name,
                'voided' => $p->isVoided(),
                'void_reason' => $p->void_reason,
            ]);

        $payload = [
            'worklist' => $worklist,
            'ledger' => $ledger,
            'canFinance' => $canFinance,
            'canRecord' => $request->user()->can('payment.record'),
            'canVoid' => $request->user()->can('payment.void'),
            'methods' => ['Cash', 'Check', 'GCash', 'Bank'],
            'types' => ['Full', 'Partial', 'Down', 'Reservation'],
        ];

        // Aggregates — admin / finance.view only.
        if ($canFinance) {
            $collected = (int) Payment::valid()->sum('amount');
            $outstanding = $worklist->sum('balance');
            $byProgram = Program::query()->where('fee', '>', 0)->get()->map(function (Program $prog) {
                $expected = $prog->applicants()->where('status', '!=', 'Disqualified')->count() * $prog->fee;
                $collected = (int) Payment::valid()
                    ->whereHas('applicant', fn ($q) => $q->where('program_id', $prog->id))
                    ->sum('amount');

                return [
                    'program' => $prog->title,
                    'expected' => $expected,
                    'collected' => $collected,
                    'pct' => $expected > 0 ? round($collected / $expected * 100) : 0,
                ];
            })->values();

            $payload['aggregates'] = [
                'collected' => $collected,
                'outstanding' => $outstanding,
                'by_program' => $byProgram,
            ];
            $payload['programs'] = Program::orderBy('title')->get(['id', 'title'])->all();
        }

        return Inertia::render('Cashier/Index', $payload);
    }

    /** Shared filter pipeline for the payments report + CSV. */
    private function filterPayments(Request $request): array
    {
        $filters = $request->only(['program', 'method', 'status', 'paid_from', 'paid_to']);

        $query = Payment::query()
            ->with(['applicant:id,first_name,last_name,middle_name,ext_name,program_id', 'applicant.program:id,title,level', 'cashier:id,name'])
            ->when($filters['program'] ?? null, fn ($q, $p) => $q->whereHas('applicant', fn ($q) => $q->where('program_id', $p)))
            ->when($filters['method'] ?? null, fn ($q, $m) => $q->where('method', $m))
            ->when(($filters['status'] ?? null) === 'valid', fn ($q) => $q->whereNull('voided_at'))
            ->when(($filters['status'] ?? null) === 'voided', fn ($q) => $q->whereNotNull('voided_at'))
            ->when($filters['paid_from'] ?? null, fn ($q, $d) => $q->whereDate('paid_at', '>=', $d))
            ->when($filters['paid_to'] ?? null, fn ($q, $d) => $q->whereDate('paid_at', '<=', $d))
            ->latest('paid_at')->latest('id');

        return [$query, $filters];
    }

    private function paymentFilterSummary(array $filters): array
    {
        $parts = [];
        if (! empty($filters['program'])) $parts[] = ['Program', Program::find($filters['program'])?->title ?? $filters['program']];
        if (! empty($filters['method'])) $parts[] = ['Method', $filters['method']];
        if (! empty($filters['status'])) $parts[] = ['Status', ucfirst($filters['status'])];
        if (! empty($filters['paid_from'])) $parts[] = ['Paid from', $filters['paid_from']];
        if (! empty($filters['paid_to'])) $parts[] = ['Paid to', $filters['paid_to']];

        return $parts;
    }

    /** Printable payments report (finance only). */
    public function report(Request $request): \Illuminate\Contracts\View\View
    {
        abort_unless($request->user()->can('finance.view'), 403);
        [$query, $filters] = $this->filterPayments($request);
        $rows = $query->get();

        return view('cashier.report', [
            'rows' => $rows,
            'summary' => $this->paymentFilterSummary($filters),
            'count' => $rows->count(),
            'collected' => (int) $rows->whereNull('voided_at')->sum('amount'),
            'voided' => (int) $rows->whereNotNull('voided_at')->sum('amount'),
            'user' => $request->user(),
        ]);
    }

    /** Filtered payments CSV (finance only). */
    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($request->user()->can('finance.view'), 403);
        [$query] = $this->filterPayments($request);
        $rows = $query->get();

        $header = ['Date', 'OR No.', 'Learner', 'Program', 'Method', 'Type', 'Amount', 'Cashier', 'Status'];
        $filename = 'payments-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows, $header) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->paid_at?->toDateString(), $p->or_number, $p->applicant?->display_name,
                    $p->applicant?->program?->title, $p->method, $p->type, $p->amount,
                    $p->cashier?->name, $p->isVoided() ? 'VOID' : 'Valid',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function record(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('payment.record'), 403);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:1000000'],
            'type' => ['required', Rule::in(['Full', 'Partial', 'Down', 'Reservation'])],
            'method' => ['required', Rule::in(['Cash', 'Check', 'GCash', 'Bank'])],
            'or_number' => ['nullable', 'string', 'max:60'],
            'paid_at' => ['required', 'date'],
        ]);

        $payment = new Payment($data);
        $payment->applicant_id = $applicant->id;
        $payment->cashier_id = $request->user()->id;
        $payment->save();

        // Pipeline advances Qualified → Paid only when fully paid.
        if ($applicant->status === 'Qualified' && $applicant->balance() === 0) {
            $applicant->update(['status' => 'Paid']);
        }

        return back()->with('success', "Payment of ₱{$payment->amount} recorded for {$applicant->display_name}.");
    }

    public function void(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->can('payment.void'), 403);
        if ($payment->isVoided()) {
            return back()->with('error', 'Payment is already voided.');
        }

        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $payment->update([
            'voided_at' => now(),
            'void_reason' => $data['reason'],
            'voided_by' => $request->user()->id,
        ]);

        // Voiding a completing payment reverts Paid → Qualified.
        $applicant = $payment->applicant;
        if ($applicant && $applicant->status === 'Paid' && $applicant->balance() > 0) {
            $applicant->update(['status' => 'Qualified']);
        }

        return back()->with('success', 'Payment voided.');
    }
}
