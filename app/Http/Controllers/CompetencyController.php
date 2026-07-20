<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\CompetencyResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Per-trainee competency rating + printable report card. This replaces the
 * batch-based Training module: ratings are recorded straight on the trainee.
 */
class CompetencyController extends Controller
{
    /** Rate a trainee's Units of Competency (Competent / Not Yet Competent / clear). */
    public function rate(Request $request, Applicant $applicant): RedirectResponse
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

    /** Printable Competency Achievement Record for one trainee. */
    public function reportCard(Request $request, Applicant $applicant): \Illuminate\Contracts\View\View
    {
        abort_unless($request->user()->can('assess'), 403);
        $applicant->load('program.competencyUnits', 'competencyResults');

        return view('training.report_card', [
            'a' => $applicant,
            'summary' => $applicant->competencySummary(),
            'user' => $request->user(),
        ]);
    }
}
