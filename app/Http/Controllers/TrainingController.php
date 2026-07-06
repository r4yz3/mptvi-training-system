<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Attendance;
use App\Models\Batch;
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
        $date = $request->input('date', now()->toDateString());

        $roster = $batch->applicants()
            ->whereIn('status', ['Paid', 'In training', 'For assessment', 'Certified'])
            ->orderBy('last_name')
            ->get()
            ->map(function (Applicant $a) use ($date) {
                $today = $a->attendances()->whereDate('date', $date)->first();

                return [
                    'id' => $a->id,
                    'name' => $a->display_name,
                    'status' => $a->status,
                    'trainee_status' => $a->trainee_status,
                    'rate' => $a->attendanceRate(),
                    'today' => $today?->status,
                ];
            });

        return Inertia::render('Training/Show', [
            'batch' => [
                'id' => $batch->id,
                'code' => $batch->code,
                'program' => $batch->program?->title,
                'start_date' => $batch->start_date?->toDateString(),
                'end_date' => $batch->end_date?->toDateString(),
            ],
            'roster' => $roster,
            'date' => $date,
            'canMark' => $request->user()->can('attendance'),
            'statuses' => ['Present', 'Absent', 'Late', 'Excused'],
            'canSetStatus' => $request->user()->can('trainee.status'),
            'traineeStatuses' => config('lpf.trainee_statuses'),
        ]);
    }

    public function mark(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('attendance'), 403);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'status' => ['required', Rule::in(['Present', 'Absent', 'Late', 'Excused'])],
        ]);

        Attendance::updateOrCreate(
            ['applicant_id' => $applicant->id, 'date' => $data['date']],
            ['status' => $data['status'], 'recorded_by' => $request->user()->id],
        );

        // First attendance promotes a Paid learner into training.
        if ($applicant->status === 'Paid') {
            $applicant->update(['status' => 'In training']);
        }

        return back()->with('success', "Attendance saved: {$data['status']}.");
    }
}
