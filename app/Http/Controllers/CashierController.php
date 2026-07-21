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

        // Worklist — learners who still owe anything (the misc fee OR a scheduled
        // extra fee like uniform / assessment), not disqualified.
        $worklist = Applicant::query()
            ->with(['program:id,title,fee', 'payments'])
            ->where('active', true)
            ->where('status', '!=', 'Disqualified')
            ->get()
            ->map(fn (Applicant $a) => [
                'id' => $a->id,
                'name' => $a->display_name,
                'program' => $a->program?->title,
                'fee' => $a->fee(),
                'paid' => $a->paidTotal(),
                'balance' => $a->balance(),
                'pay_status' => $a->payStatus(),
                'status' => $a->status,
                // Scheduled extra fees still owed (empty when all paid / none set).
                'extras' => collect($a->scheduledFees())->where('balance', '>', 0)->values()->all(),
                'extras_balance' => $a->scheduledFeesBalance(),
            ])
            ->filter(fn ($r) => $r['balance'] > 0 || $r['extras_balance'] > 0)
            ->sortByDesc(fn ($r) => $r['balance'] + $r['extras_balance'])
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
                'category' => $p->category,
                'description' => $p->description,
                'type' => $p->type,
                'method' => $p->method,
                'or_number' => $p->or_number,
                'paid_at' => $p->paid_at?->toDateString(),
                'cashier' => $p->cashier?->name,
                'voided' => $p->isVoided(),
                'void_reason' => $p->void_reason,
            ]);

        $canRecord = $request->user()->can('payment.record');

        // Learner picker for "Receive payment" — any active, non-disqualified learner.
        $learners = $canRecord
            ? Applicant::query()->with('program:id,title,fee')
                ->where('active', true)->where('status', '!=', 'Disqualified')
                ->orderBy('last_name')->orderBy('first_name')->get()
                ->map(fn (Applicant $a) => [
                    'id' => $a->id,
                    'name' => $a->display_name,
                    'program' => $a->program?->title,
                    'balance' => $a->balance(),
                ])->values()
            : [];

        $payload = [
            'worklist' => $worklist,
            'ledger' => $ledger,
            'learners' => $learners,
            'canFinance' => $canFinance,
            'canRecord' => $canRecord,
            'canVoid' => $request->user()->can('payment.void'),
            'methods' => ['Cash', 'Check', 'GCash', 'Bank'],
            'types' => ['Full Payment', 'Partial'],
            'categories' => config('lpf.payment_categories'),
            'trainingFeeCategory' => config('lpf.training_fee_category'),
            'otherCategory' => config('lpf.other_category'),
        ];

        // Aggregates — admin / finance.view only.
        if ($canFinance) {
            $trainingCat = config('lpf.training_fee_category');
            $collected = (int) Payment::valid()->sum('amount');
            $feeCollected = (int) Payment::valid()->where('category', $trainingCat)->sum('amount');
            $outstanding = $worklist->sum('balance');

            // Program fee collection % — training-fee payments only (other items don't count).
            $byProgram = Program::query()->where('fee', '>', 0)->get()->map(function (Program $prog) use ($trainingCat) {
                $expected = $prog->applicants()->where('status', '!=', 'Disqualified')->count() * $prog->fee;
                $collected = (int) Payment::valid()->where('category', $trainingCat)
                    ->whereHas('applicant', fn ($q) => $q->where('program_id', $prog->id))
                    ->sum('amount');

                return [
                    'program' => $prog->title,
                    'expected' => $expected,
                    'collected' => $collected,
                    'pct' => $expected > 0 ? round($collected / $expected * 100) : 0,
                ];
            })->values();

            // Collections grouped by category (training fee + uniform + …).
            $byCategory = Payment::valid()
                ->selectRaw('category, SUM(amount) as total, COUNT(*) as cnt')
                ->groupBy('category')->orderByDesc('total')->get()
                ->map(fn ($r) => ['category' => $r->category, 'total' => (int) $r->total, 'count' => (int) $r->cnt])
                ->values();

            $payload['aggregates'] = [
                'collected' => $collected,
                'fee_collected' => $feeCollected,
                'other_collected' => $collected - $feeCollected,
                'outstanding' => $outstanding,
                'by_program' => $byProgram,
                'by_category' => $byCategory,
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

        $otherCategory = config('lpf.other_category');
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:1000000'],
            'category' => ['nullable', Rule::in(config('lpf.payment_categories'))],
            // "Others" requires the cashier to specify what the collection is for.
            'description' => ['nullable', 'string', 'max:160', Rule::requiredIf(fn () => $request->input('category') === $otherCategory)],
            'type' => ['required', Rule::in(['Full Payment', 'Partial'])],
            'method' => ['required', Rule::in(['Cash', 'Check', 'GCash', 'Bank'])],
            'or_number' => ['nullable', 'string', 'max:60'],
            'paid_at' => ['required', 'date'],
        ], [
            'description.required' => 'Please specify what the “Others” collection is for.',
        ]);
        // Default to the training fee when no category is supplied (legacy behavior).
        $data['category'] = $data['category'] ?? config('lpf.training_fee_category');
        // Auto-generate a sequential OR number (OR-0001, OR-0002, …) unless one was given.
        $data['or_number'] = filled($data['or_number'] ?? null) ? $data['or_number'] : $this->nextOrNumber();

        $payment = new Payment($data);
        $payment->applicant_id = $applicant->id;
        $payment->cashier_id = $request->user()->id;
        $payment->save();

        // Any training-fee payment — even a partial one — enrolls the trainee
        // straight into training; no separate screening/qualification or
        // start-training step is required. Advance from Registered (not yet
        // screened) or Enrolled; never from Disqualified or later stages. The
        // Partial/Paid pay-status still reflects the real balance.
        if ($payment->category === config('lpf.training_fee_category')
            && in_array($applicant->status, ['Registered', 'Enrolled'], true)) {
            $applicant->update(['status' => 'In training']);
        }

        return back()
            ->with('success', "Payment of ₱{$payment->amount} ({$payment->category}) recorded for {$applicant->display_name}.")
            ->with('receipt_id', $payment->id);
    }

    /** Next sequential OR number: OR-0001, OR-0002, … (continues from the highest existing). */
    private function nextOrNumber(): string
    {
        $max = Payment::query()
            ->where('or_number', 'like', 'OR-%')
            ->pluck('or_number')
            ->map(fn ($or) => (int) substr($or, 3))
            ->max() ?? 0;

        return 'OR-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }

    /** Printable statement of account for one learner — fee, all payments, balance. */
    public function statement(Request $request, Applicant $applicant): \Illuminate\Contracts\View\View
    {
        abort_unless($request->user()->can('payment.record') || $request->user()->can('finance.view'), 403);
        $applicant->load('program:id,title,level,fee');

        $trainingCat = config('lpf.training_fee_category');

        // All non-voided payments, oldest first, so the running fee balance reads top-down.
        $payments = $applicant->payments()
            ->with('cashier:id,name')
            ->orderBy('paid_at')->orderBy('id')
            ->get();

        $fee = $applicant->fee();
        $running = $fee;
        $rows = $payments->map(function (Payment $p) use ($trainingCat, &$running) {
            $isFee = $p->category === $trainingCat;
            // Only valid training-fee payments draw down the program-fee balance.
            if ($isFee && ! $p->isVoided()) {
                $running = max(0, $running - (int) $p->amount);
            }

            return [
                'date' => $p->paid_at,
                'or_number' => $p->or_number,
                'category' => $p->category,
                'description' => $p->description,
                'method' => $p->method,
                'type' => $p->type,
                'amount' => (int) $p->amount,
                'is_fee' => $isFee,
                'voided' => $p->isVoided(),
                'balance' => $isFee ? $running : null,
                'cashier' => $p->cashier?->name,
            ];
        });

        return view('cashier.statement', [
            'a' => $applicant,
            'rows' => $rows,
            'fee' => $fee,
            'paid' => $applicant->paidTotal(),
            'balance' => $applicant->balance(),
            'other' => $applicant->otherCollected(),
            'payStatus' => $applicant->payStatus(),
            'extras' => $applicant->scheduledFees(),
            'user' => $request->user(),
        ]);
    }

    /**
     * End-of-day cash report for reconciliation. Finance sees all cashiers (with
     * an optional cashier filter); a plain cashier sees only their own day.
     */
    public function daily(Request $request): \Illuminate\Contracts\View\View
    {
        abort_unless($request->user()->can('payment.record') || $request->user()->can('finance.view'), 403);

        $canFinance = $request->user()->can('finance.view');
        $date = $request->date('date') ?? now();
        $cashierId = $canFinance ? $request->integer('cashier') : $request->user()->id;

        $payments = Payment::query()
            ->with(['applicant:id,first_name,last_name,middle_name,ext_name', 'cashier:id,name'])
            ->whereDate('paid_at', $date)
            ->when($cashierId, fn ($q) => $q->where('cashier_id', $cashierId))
            ->orderBy('or_number')->orderBy('id')
            ->get();

        $valid = $payments->reject->isVoided();

        $byMethod = $valid->groupBy('method')
            ->map(fn ($g, $m) => ['method' => $m, 'count' => $g->count(), 'total' => (int) $g->sum('amount')])
            ->sortByDesc('total')->values();

        $byCategory = $valid->groupBy('category')
            ->map(fn ($g, $c) => ['category' => $c, 'count' => $g->count(), 'total' => (int) $g->sum('amount')])
            ->sortByDesc('total')->values();

        // Per-cashier breakdown is only meaningful when finance views all cashiers unfiltered.
        $byCashier = ($canFinance && ! $cashierId)
            ? $valid->groupBy(fn (Payment $p) => $p->cashier?->name ?? '—')
                ->map(fn ($g, $name) => ['cashier' => $name, 'count' => $g->count(), 'total' => (int) $g->sum('amount')])
                ->sortByDesc('total')->values()
            : collect();

        $ors = $valid->pluck('or_number')->filter()->sort()->values();

        return view('cashier.daily', [
            'date' => $date,
            'rows' => $payments,
            'collected' => (int) $valid->sum('amount'),
            'count' => $valid->count(),
            'voidedCount' => $payments->count() - $valid->count(),
            'voidedTotal' => (int) $payments->filter->isVoided()->sum('amount'),
            'byMethod' => $byMethod,
            'byCategory' => $byCategory,
            'byCashier' => $byCashier,
            'orFrom' => $ors->first(),
            'orTo' => $ors->last(),
            'cashierName' => $cashierId ? (\App\Models\User::find($cashierId)?->name) : null,
            'showCashierCol' => $canFinance && ! $cashierId,
            'user' => $request->user(),
        ]);
    }

    /** Printable acknowledgement receipt for a single payment. */
    public function receipt(Request $request, Payment $payment): \Illuminate\Contracts\View\View
    {
        abort_unless($request->user()->can('payment.record') || $request->user()->can('finance.view'), 403);
        $payment->load(['applicant.program', 'cashier:id,name']);

        return view('cashier.receipt', [
            'p' => $payment,
            'a' => $payment->applicant,
            'amountWords' => \App\Support\Money::inWords((int) $payment->amount),
            'inst' => \App\Models\Setting::institution(),
        ]);
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

        // Un-enroll only when the void leaves the trainee with no valid training-fee
        // payment at all (they may still have other partial payments on record).
        // Enrolment now lands them at "In training"; roll back to Enrolled.
        $applicant = $payment->applicant;
        if ($applicant && $applicant->status === 'In training' && $applicant->paidTotal() === 0) {
            $applicant->update(['status' => 'Enrolled']);
        }

        return back()->with('success', 'Payment voided.');
    }
}
