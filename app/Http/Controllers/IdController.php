<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Program;
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
        $program = $request->input('program');

        $applicants = Applicant::query()
            ->with('program:id,title,level')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")))
            ->when($program, fn ($q) => $q->where('program_id', $program))
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
            'filters' => ['search' => $search, 'program' => $program ? (string) $program : ''],
            'programs' => Program::orderBy('title')->get(['id', 'title'])
                ->map(fn (Program $p) => ['id' => $p->id, 'title' => $p->title]),
            'canIssue' => $request->user()->can('id.issue'),
            'stats' => ['total' => $total, 'issued' => $issued, 'pending' => $total - $issued],
        ]);
    }

    public function card(Request $request, Applicant $applicant): Response
    {
        $applicant->load('program');

        return Inertia::render('Id/Card', [
            'applicant' => $this->cardData($applicant),
            'canIssue' => $request->user()->can('id.issue'),
            'signatory' => Setting::signatories()['approved_by'],
        ]);
    }

    /**
     * Bulk ID sheet — tiles many trainee cards on A4 (9 per page). Accepts either
     * a whole `program` or a comma-separated `ids` list (hand-picked on the ID list).
     */
    public function sheet(Request $request): Response
    {
        $programId = $request->input('program');
        $ids = collect(explode(',', (string) $request->input('ids')))
            ->map(fn ($i) => (int) trim($i))->filter()->values();

        $query = Applicant::query()
            ->with('program:id,title,level')
            ->orderBy('last_name')->orderBy('first_name');

        if ($programId) {
            $query->where('program_id', $programId);
        } elseif ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids);
        } else {
            $query->whereRaw('1 = 0'); // nothing selected
        }

        $applicants = $query->get()->map(fn (Applicant $a) => $this->cardData($a))->values();
        $label = $programId
            ? (Program::find($programId)?->title ?? 'Program')
            : $applicants->count() . ' selected';

        return Inertia::render('Id/Sheet', [
            'applicants' => $applicants,
            'signatory' => Setting::signatories()['approved_by'],
            'label' => $label,
        ]);
    }

    /** Card payload shared by the single-card page and the bulk sheet. */
    private function cardData(Applicant $applicant): array
    {
        return [
            'id' => $applicant->id,
            'name' => $applicant->display_name,
            'photo_url' => $applicant->photo_url,
            'program' => $applicant->program?->title,
            'level' => $applicant->program?->level,
            'barangay' => $applicant->barangay,
            'province' => $applicant->province,
            'contact' => $applicant->contact,
            'emergency_name' => $applicant->emergency_name,
            'emergency_contact' => $applicant->emergency_contact,
            'school_year' => $applicant->school_year,
            'issued' => $applicant->id_issued_at?->toDateString(),
        ];
    }

    public function issue(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('id.issue'), 403);
        $applicant->update(['id_issued_at' => now()->toDateString()]);

        return back()->with('success', "ID issued for {$applicant->display_name}.");
    }
}
