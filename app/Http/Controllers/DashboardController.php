<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\CustomField;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $role = $user->roleKey();

        $byStatus = Applicant::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $stat = fn (string $s) => (int) ($byStatus[$s] ?? 0);

        // Assessment outcome counts (the manual Competent / Not Yet Competent result).
        $byResult = Applicant::query()->whereNotNull('assessment_result')
            ->selectRaw('assessment_result, count(*) as c')->groupBy('assessment_result')->pluck('c', 'assessment_result');
        $competent = (int) ($byResult['Competent'] ?? 0);
        $notYet = (int) ($byResult['Not Yet Competent'] ?? 0);

        // Cards shown depend on role (least-privilege).
        $cards = [];
        $pipeline = null;

        if (in_array($role, ['admin', 'manager', 'registrar'], true)) {
            // Registered → Enrolled (screened) → In training (paid). Assessment
            // (Competent / Not Yet) is a separate result, shown in the cards.
            $pipeline = [
                ['label' => 'Registered', 'value' => $stat('Registered')],
                ['label' => 'Enrolled', 'value' => $stat('Enrolled')],
                ['label' => 'In training', 'value' => $stat('In training')],
            ];
            $cards = [
                ['label' => 'Total applicants', 'value' => (int) Applicant::count(), 'tone' => 'brand'],
                ['label' => 'Awaiting screening', 'value' => $stat('Registered'), 'tone' => 'amber'],
                ['label' => 'In training', 'value' => $stat('In training'), 'tone' => 'indigo'],
                ['label' => 'Competent', 'value' => $competent, 'tone' => 'emerald'],
            ];
            if ($user->can('finance.view')) {
                $cards[] = ['label' => 'Total collected', 'value' => '₱' . number_format((int) Payment::valid()->sum('amount')), 'tone' => 'emerald'];
            }
        } elseif ($role === 'cashier') {
            // Operational worklist only — NO finance analytics (finance privacy).
            $owing = Applicant::with('program:id,fee')->where('active', true)->where('status', '!=', 'Disqualified')->get()
                ->filter(fn (Applicant $a) => $a->fee() > 0 && $a->balance() > 0)->count();
            $cards = [
                ['label' => 'Accounts to collect', 'value' => $owing, 'tone' => 'amber'],
                ['label' => 'My payments today', 'value' => (int) Payment::where('cashier_id', $user->id)->whereDate('paid_at', now())->count(), 'tone' => 'brand'],
            ];
        } elseif ($role === 'coordinator') {
            $cards = [
                ['label' => 'In training', 'value' => $stat('In training'), 'tone' => 'indigo'],
                ['label' => 'Competent', 'value' => $competent, 'tone' => 'emerald'],
                ['label' => 'Not yet competent', 'value' => $notYet, 'tone' => 'amber'],
            ];
        }

        return Inertia::render('Dashboard', [
            'role' => $role,
            'roleLabel' => config('rbac.roles')[$role] ?? $role,
            'cards' => $cards,
            'pipeline' => $pipeline,
            'customBreakdowns' => in_array($role, ['admin', 'manager', 'registrar'], true) ? $this->customBreakdowns() : [],
        ]);
    }

    /** Count breakdowns for custom fields flagged "show on dashboard" (select/checkbox). */
    private function customBreakdowns(): array
    {
        $out = [];
        $fields = CustomField::where('enabled', true)->where('show_on_dashboard', true)->get();

        foreach ($fields as $f) {
            if ($f->type === 'select' && $f->options) {
                $items = collect($f->options)->map(fn ($opt) => [
                    'label' => $opt,
                    'value' => Applicant::where("custom_data->{$f->key}", $opt)->count(),
                ])->all();
            } elseif ($f->type === 'checkbox') {
                $items = [
                    ['label' => 'Yes', 'value' => Applicant::where("custom_data->{$f->key}", true)->count()],
                    ['label' => 'No', 'value' => Applicant::where("custom_data->{$f->key}", false)->count()],
                ];
            } else {
                continue;
            }
            $out[] = ['label' => $f->label, 'items' => $items];
        }

        return $out;
    }
}
