<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\CompetencyResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrainingController extends Controller
{
    public function index(): Response
    {
        $batches = Batch::query()
            ->with('program:id,title,level')
            ->withCount(['applicants as trainees_count' => fn ($q) => $q->whereIn('status', ['Paid', 'In training', 'For assessment', 'Certified'])])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Batch $b) => [
                'id' => $b->id,
                'code' => $b->code,
                'program' => $b->program?->title,
                'level' => $b->program?->level,
                'session' => $b->class_session,
                'status' => $b->status,
                'trainees' => $b->trainees_count,
            ]);

        return Inertia::render('Training/Index', ['batches' => $batches]);
    }

    public function show(Request $request, Batch $batch): Response
    {
        $batch->loadMissing('program.competencyUnits');
        $units = $batch->program?->competencyUnits ?? collect();

        $roster = $batch->applicants()
            ->whereIn('status', ['Paid', 'In training', 'For assessment', 'Certified'])
            ->with('competencyResults')
            ->orderBy('last_name')
            ->get()
            ->map(fn (Applicant $a) => [
                'id' => $a->id,
                'name' => $a->display_name,
                'status' => $a->status,
                'trainee_status' => $a->trainee_status,
                'competency' => $a->competencySummary(),
            ]);

        return Inertia::render('Training/Show', [
            'batch' => [
                'id' => $batch->id,
                'code' => $batch->code,
                'program' => $batch->program?->title,
                'program_id' => $batch->program_id,
                'start_date' => $batch->start_date?->toDateString(),
                'end_date' => $batch->end_date?->toDateString(),
            ],
            'roster' => $roster,
            'canSetStatus' => $request->user()->can('trainee.status'),
            'traineeStatuses' => config('lpf.trainee_statuses'),
            'canGrade' => $request->user()->can('assess'),
            'canStartTraining' => $request->user()->can('attendance'),
            // The batch program's Units of Competency (Basic/Common/Core) for the rating sheet.
            'units' => $units->map(fn ($u) => [
                'id' => $u->id, 'code' => $u->code, 'title' => $u->title, 'type' => $u->type,
            ])->values(),
            'results' => CompetencyResult::RESULTS,
        ]);
    }

    /**
     * Institutional competency evaluation — rate a trainee's Units of Competency
     * Competent / Not Yet Competent. Accepts the full unit list; a null result
     * clears that unit's rating. Only units of the trainee's program are stored.
     */
    public function rateCompetency(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('assess'), 403);

        $data = $request->validate([
            'ratings' => ['present', 'array'],
            'ratings.*.unit_id' => ['required', 'integer'],
            'ratings.*.result' => ['nullable', Rule::in(CompetencyResult::RESULTS)],
            'ratings.*.remarks' => ['nullable', 'string', 'max:255'],
            'rated_at' => ['required', 'date'],
        ]);

        // Guard: only accept units that actually belong to this trainee's program.
        $validUnitIds = $applicant->program?->competencyUnits()->pluck('id')->all() ?? [];

        foreach ($data['ratings'] as $row) {
            if (! in_array((int) $row['unit_id'], $validUnitIds, true)) {
                continue;
            }
            $result = $row['result'] ?? null;

            if ($result === null || $result === '') {
                // Blank clears any existing rating for this unit.
                CompetencyResult::where('applicant_id', $applicant->id)
                    ->where('competency_unit_id', $row['unit_id'])->delete();

                continue;
            }

            CompetencyResult::updateOrCreate(
                ['applicant_id' => $applicant->id, 'competency_unit_id' => $row['unit_id']],
                ['result' => $result, 'remarks' => $row['remarks'] ?? null,
                    'rated_at' => $data['rated_at'], 'rated_by' => $request->user()->id],
            );
        }

        // All units Competent promotes an active trainee's training status to Completed.
        if ($applicant->fresh()->competencySummary()['complete']
            && in_array($applicant->trainee_status, [null, 'Active'], true)) {
            $applicant->update(['trainee_status' => 'Completed']);
        }

        return back()->with('success', "Competencies updated for {$applicant->display_name}.");
    }

    /** Printable batch-wide Achievement Chart — every trainee × Unit of Competency. */
    public function classRecord(Request $request, Batch $batch): \Illuminate\Contracts\View\View
    {
        abort_unless($request->user()->can('assess'), 403);
        $batch->load('program.competencyUnits');
        $units = $batch->program?->competencyUnits ?? collect();

        $roster = $batch->applicants()
            ->whereIn('status', ['Paid', 'In training', 'For assessment', 'Certified'])
            ->orderBy('last_name')->orderBy('first_name')
            ->with('competencyResults')
            ->get()
            ->map(fn (Applicant $a) => [
                'name' => $a->full_name,
                'summary' => $a->competencySummary(),
            ]);

        return view('training.class_record', [
            'batch' => $batch,
            'units' => $units,
            'roster' => $roster,
            'user' => $request->user(),
        ]);
    }

    /** Printable Competency Achievement Record for one trainee. */
    public function reportCard(Request $request, Applicant $applicant): \Illuminate\Contracts\View\View
    {
        abort_unless($request->user()->can('assess'), 403);
        $applicant->load('program.competencyUnits', 'batch:id,code', 'competencyResults');

        return view('training.report_card', [
            'a' => $applicant,
            'summary' => $applicant->competencySummary(),
            'user' => $request->user(),
        ]);
    }

    /** Move a Paid learner into training (replaces the old first-attendance trigger). */
    public function startTraining(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('attendance'), 403);

        if ($applicant->status !== 'Paid') {
            return back()->with('error', 'Only a Paid learner can be moved into training.');
        }

        $applicant->update(['status' => 'In training']);

        return back()->with('success', "{$applicant->display_name} is now in training.");
    }
}
