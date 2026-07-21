<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    /**
     * Read-only roster of trainees in the assessment phase. The Competent /
     * Not Yet Competent result is set on each trainee's profile (no certificate).
     */
    public function index(Request $request): Response
    {
        $priority = ['In training' => 0, 'For assessment' => 1, 'Certified' => 2];

        $applicants = Applicant::query()
            ->with('program:id,title,level')
            ->whereIn('status', ['In training', 'For assessment', 'Certified'])
            ->orderBy('last_name')
            ->get()
            ->sortBy(fn (Applicant $a) => $priority[$a->status] ?? 9)
            ->values()
            ->map(fn (Applicant $a) => [
                'id' => $a->id,
                'name' => $a->display_name,
                'program' => $a->program?->title,
                'level' => $a->program?->level,
                'status' => $a->status,
                'result' => $a->assessment_result,
                'competency' => $a->competencySummary(),
            ]);

        return Inertia::render('Assessment/Index', [
            'applicants' => $applicants,
            'canAssess' => $request->user()->can('assess'),
        ]);
    }
}
