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

        // Cards shown depend on role (least-privilege — cashier sees no ₱ totals).
        $cards = [];
        $pipeline = null;

        if (in_array($role, ['admin', 'manager'], true)) {
            $pipeline = [
                ['label' => 'Registered', 'value' => $stat('Registered')],
                ['label' => 'Qualified', 'value' => $stat('Qualified')],
                ['label' => 'Paid', 'value' => $stat('Paid')],
                ['label' => 'In training', 'value' => $stat('In training')],
                ['label' => 'For assessment', 'value' => $stat('For assessment')],
                ['label' => 'Certified', 'value' => $stat('Certified')],
            ];
            $cards = [
                ['label' => 'Total applicants', 'value' => (int) Applicant::count(), 'tone' => 'brand'],
                ['label' => 'Awaiting screening', 'value' => $stat('Registered'), 'tone' => 'amber'],
                ['label' => 'In training', 'value' => $stat('In training'), 'tone' => 'indigo'],
                ['label' => 'Certified', 'value' => $stat('Certified'), 'tone' => 'emerald'],
            ];
            if ($user->can('finance.view')) {
                $cards[] = ['label' => 'Total collected', 'value' => '₱' . number_format((int) Payment::valid()->sum('amount')), 'tone' => 'emerald'];
            }
        } elseif ($role === 'registrar') {
            $cards = [
                ['label' => 'Total applicants', 'value' => (int) Applicant::count(), 'tone' => 'brand'],
                ['label' => 'Newly registered', 'value' => $stat('Registered'), 'tone' => 'amber'],
                ['label' => 'Qualified', 'value' => $stat('Qualified'), 'tone' => 'indigo'],
                ['label' => 'Inactive', 'value' => (int) Applicant::where('active', false)->count(), 'tone' => 'slate'],
            ];
        } elseif ($role === 'cashier') {
            // No ₱ totals — a payment worklist only.
            $owing = Applicant::with('program:id,fee')->where('active', true)->where('status', '!=', 'Disqualified')->get()
                ->filter(fn (Applicant $a) => $a->balance() > 0)->count();
            $cards = [
                ['label' => 'Accounts to collect', 'value' => $owing, 'tone' => 'amber'],
                ['label' => 'Fully paid', 'value' => $stat('Paid') + $stat('In training') + $stat('For assessment') + $stat('Certified'), 'tone' => 'emerald'],
                ['label' => 'My payments today', 'value' => (int) Payment::where('cashier_id', $user->id)->whereDate('paid_at', now())->count(), 'tone' => 'brand'],
            ];
        } elseif ($role === 'coordinator') {
            $cards = [
                ['label' => 'In training', 'value' => $stat('In training'), 'tone' => 'indigo'],
                ['label' => 'For assessment', 'value' => $stat('For assessment'), 'tone' => 'amber'],
                ['label' => 'Certified', 'value' => $stat('Certified'), 'tone' => 'emerald'],
            ];
        }

        return Inertia::render('Dashboard', [
            'role' => $role,
            'roleLabel' => config('rbac.roles')[$role] ?? $role,
            'cards' => $cards,
            'pipeline' => $pipeline,
            'customBreakdowns' => in_array($role, ['admin', 'manager'], true) ? $this->customBreakdowns() : [],
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
