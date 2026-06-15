<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Batch;
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
            'uli' => $a->uli,
            'name' => $a->display_name,
            'age' => $a->age,
            'program' => $a->program?->title,
            'program_id' => $a->program_id,
            'level' => $a->program?->level,
            'education' => $a->education,
            'class_session' => $a->class_session,
            'batch_id' => $a->batch_id,
            'status' => $a->status,
            'eligibility' => $a->eligibility(),
            'eligible' => $a->isEligible(),
            'disqualify_reason' => $a->disqualify_reason,
        ]);

        // Assignable batches (not finished) so the Qualify step can place the learner into one.
        $batches = Batch::query()->withCount('applicants')
            ->whereNotIn('status', ['Closed', 'Completed'])
            ->orderBy('code')->get()
            ->map(fn (Batch $b) => [
                'id' => $b->id,
                'program_id' => $b->program_id,
                'code' => $b->code,
                'session' => $b->class_session,
                'days' => $b->class_days,
                'capacity' => $b->capacity,
                'used' => $b->applicants_count,
                'status' => $b->status,
            ]);

        return Inertia::render('Screening/Index', [
            'applicants' => $applicants,
            'batches' => $batches,
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

        $data = $request->validate(['batch_id' => ['nullable', 'integer', 'exists:batches,id']]);

        $update = [
            'status' => 'Qualified',
            'screened_at' => now(),
            'screened_by' => $request->user()->id,
            'disqualify_reason' => null,
        ];

        $batch = null;
        if (! empty($data['batch_id'])) {
            $batch = Batch::find($data['batch_id']);
            // A batch can only hold learners of its own program.
            abort_if($batch->program_id !== $applicant->program_id, 422, 'That batch belongs to a different program.');
            $update['batch_id'] = $batch->id;
        }

        $applicant->update($update);

        return back()->with('success', $batch
            ? "“{$applicant->display_name}” qualified and assigned to batch {$batch->code}."
            : "“{$applicant->display_name}” marked Qualified.");
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
