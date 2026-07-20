<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScreeningController extends Controller
{
    public function index(Request $request): Response
    {
        // Default queue = newly registered (awaiting screening). A tab can show all.
        $tab = $request->input('tab', 'pending');

        $query = Applicant::query()->with('program:id,title,level')->orderByDesc('id');

        if ($tab === 'pending') {
            $query->where('status', 'Registered');
        } elseif ($tab === 'qualified') {
            $query->where('status', 'Qualified');
        } elseif ($tab === 'disqualified') {
            $query->where('status', 'Disqualified');
        }

        $applicants = $query->paginate(12)->withQueryString()->through(fn (Applicant $a) => [
            'id' => $a->id,
            'name' => $a->display_name,
            'age' => $a->age,
            'program' => $a->program?->title,
            'program_id' => $a->program_id,
            'level' => $a->program?->level,
            'education' => $a->education,
            'class_session' => $a->class_session,
            'status' => $a->status,
            'eligibility' => $a->eligibility(),
            'eligible' => $a->isEligible(),
            'disqualify_reason' => $a->disqualify_reason,
        ]);

        return Inertia::render('Screening/Index', [
            'applicants' => $applicants,
            'tab' => $tab,
            'counts' => [
                'pending' => Applicant::where('status', 'Registered')->count(),
                'qualified' => Applicant::where('status', 'Qualified')->count(),
                'disqualified' => Applicant::where('status', 'Disqualified')->count(),
            ],
        ]);
    }

    public function qualify(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('screen'), 403);

        if (! in_array($applicant->status, ['Registered', 'Disqualified'], true)) {
            return back()->with('error', 'Only registered or disqualified applicants can be qualified.');
        }

        $applicant->update([
            'status' => 'Qualified',
            'screened_at' => now(),
            'screened_by' => $request->user()->id,
            'disqualify_reason' => null,
        ]);

        return back()->with('success', "“{$applicant->display_name}” marked Qualified.");
    }

    public function disqualify(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('screen'), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $applicant->update([
            'status' => 'Disqualified',
            'screened_at' => now(),
            'screened_by' => $request->user()->id,
            'disqualify_reason' => $data['reason'],
        ]);

        return back()->with('success', "“{$applicant->display_name}” marked Disqualified.");
    }
}
