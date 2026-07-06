<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IdController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $applicants = Applicant::query()
            ->with('program:id,title,level', 'batch:id,code')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")))
            ->orderBy('last_name')
            ->paginate(12)->withQueryString()
            ->through(fn (Applicant $a) => [
                'id' => $a->id,
                'name' => $a->display_name,
                'program' => $a->program?->title,
                'status' => $a->status,
                'issued' => $a->id_issued_at?->toDateString(),
            ]);

        $total = Applicant::count();
        $issued = Applicant::whereNotNull('id_issued_at')->count();

        return Inertia::render('Id/Index', [
            'applicants' => $applicants,
            'filters' => ['search' => $search],
            'canIssue' => $request->user()->can('id.issue'),
            'stats' => ['total' => $total, 'issued' => $issued, 'pending' => $total - $issued],
        ]);
    }

    public function card(Request $request, Applicant $applicant): Response
    {
        $applicant->load('program', 'batch');

        return Inertia::render('Id/Card', [
            'applicant' => [
                'id' => $applicant->id,
                'name' => $applicant->display_name,
                'photo_url' => $applicant->photo_url,
                'program' => $applicant->program?->title,
                'level' => $applicant->program?->level,
                'batch' => $applicant->batch?->code,
                'barangay' => $applicant->barangay,
                'province' => $applicant->province,
                'contact' => $applicant->contact,
                'emergency_name' => $applicant->emergency_name,
                'emergency_contact' => $applicant->emergency_contact,
                'school_year' => $applicant->school_year,
                'issued' => $applicant->id_issued_at?->toDateString(),
            ],
            'canIssue' => $request->user()->can('id.issue'),
            'signatory' => Setting::signatories()['approved_by'],
        ]);
    }

    public function issue(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('id.issue'), 403);
        $applicant->update(['id_issued_at' => now()->toDateString()]);

        return back()->with('success', "ID issued for {$applicant->display_name}.");
    }
}
