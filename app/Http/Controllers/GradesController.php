<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\SubjectGrade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Major/Minor numeric grading, entered by the registrar on the trainee profile,
 * plus a printable Report of Grades. Replaces the old competency rating.
 */
class GradesController extends Controller
{
    /** Record a trainee's numeric grades (1.00–5.00) per subject; blank clears one. */
    public function save(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('assess'), 403);

        $data = $request->validate([
            'grades' => ['present', 'array'],
            'grades.*.subject_id' => ['required', 'integer'],
            'grades.*.grade' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'grades.*.remarks' => ['nullable', 'string', 'max:255'],
            'graded_at' => ['required', 'date'],
        ]);

        // Only accept subjects that belong to this trainee's program.
        $validIds = $applicant->program?->subjects()->pluck('id')->all() ?? [];

        foreach ($data['grades'] as $row) {
            if (! in_array((int) $row['subject_id'], $validIds, true)) {
                continue;
            }
            $grade = $row['grade'] ?? null;

            if ($grade === null || $grade === '') {
                SubjectGrade::where('applicant_id', $applicant->id)
                    ->where('subject_id', $row['subject_id'])->delete();

                continue;
            }

            SubjectGrade::updateOrCreate(
                ['applicant_id' => $applicant->id, 'subject_id' => $row['subject_id']],
                ['grade' => round((float) $grade, 2), 'remarks' => $row['remarks'] ?? null,
                    'graded_at' => $data['graded_at'], 'graded_by' => $request->user()->id],
            );
        }

        // All subjects graded and none failed → training status Completed.
        $summary = $applicant->fresh()->load('program.subjects', 'subjectGrades')->gradeSummary();
        if ($summary['complete'] && $summary['remark'] === 'Passed'
            && in_array($applicant->trainee_status, [null, 'Active'], true)) {
            $applicant->update(['trainee_status' => 'Completed']);
        }

        return back()->with('success', "Grades updated for {$applicant->display_name}.");
    }

    /** Printable Report of Grades (Major/Minor + GWA) for one trainee. */
    public function slip(Request $request, Applicant $applicant): \Illuminate\Contracts\View\View
    {
        abort_unless($request->user()->can('assess'), 403);
        $applicant->load('program.subjects', 'subjectGrades');

        return view('grades.slip', [
            'a' => $applicant,
            'summary' => $applicant->gradeSummary(),
            'user' => $request->user(),
        ]);
    }
}
