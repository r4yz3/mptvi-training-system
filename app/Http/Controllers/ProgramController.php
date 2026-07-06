<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function index(): Response
    {
        $programs = Program::query()
            ->withCount(['batches', 'applicants'])
            ->with(['batches' => fn ($q) => $q->withCount('applicants')->orderByDesc('id')])
            ->orderBy('title')
            ->get();

        // Assignable learners for the add-to-batch / add-to-program pickers.
        $learners = Applicant::query()
            ->where('active', true)->where('status', '!=', 'Disqualified')
            ->orderBy('last_name')->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'ext_name', 'program_id', 'batch_id', 'class_session', 'status'])
            ->map(fn (Applicant $a) => [
                'id' => $a->id,
                'name' => $a->display_name,
                'program_id' => $a->program_id,
                'batch_id' => $a->batch_id,
                'session' => $a->class_session,
                'status' => $a->status,
            ])->all();

        return Inertia::render('Programs/Index', [
            'programs' => $programs,
            'learners' => $learners,
            'options' => [
                'levels' => ['NC I', 'NC II', 'NC III', 'NC IV', 'Non-NC'],
                'class_sessions' => ['Morning', 'Afternoon', 'Whole-day'],
                'class_days' => ['Mon–Fri', 'MWF', 'Tue-Thu', 'Mon-Sat', 'Sat'],
                'batch_statuses' => ['Planned', 'Open', 'Ongoing', 'Closed', 'Completed'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('program.manage'), 403);
        $data = $this->validateProgram($request);
        Program::create($data);

        return back()->with('success', "Program “{$data['title']}” created.");
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        abort_unless($request->user()->can('program.manage'), 403);
        $program->update($this->validateProgram($request));

        return back()->with('success', "Program “{$program->title}” updated.");
    }

    /** Move an existing learner into this program (unassigns any batch from the old program). */
    public function assign(Request $request, Program $program): RedirectResponse
    {
        abort_unless($request->user()->can('program.manage'), 403);

        $data = $request->validate([
            'applicant_id' => ['required', 'integer', 'exists:applicants,id'],
        ]);
        $applicant = Applicant::findOrFail($data['applicant_id']);

        if (! $applicant->active || $applicant->status === 'Disqualified') {
            return back()->with('error', 'Only active, non-disqualified learners can be moved to a program.');
        }
        if ($applicant->program_id === $program->id) {
            return back()->with('error', "“{$applicant->display_name}” is already in {$program->title}.");
        }

        // A batch belongs to one program, so a program move always clears the old batch.
        $applicant->update(['program_id' => $program->id, 'batch_id' => null]);

        return back()->with('success', "“{$applicant->display_name}” moved to {$program->title}.");
    }

    public function destroy(Request $request, Program $program): RedirectResponse
    {
        abort_unless($request->user()->can('program.manage'), 403);

        if ($program->applicants()->exists()) {
            return back()->with('error', 'Cannot delete a program that has applicants.');
        }
        $title = $program->title;
        $program->delete();

        return back()->with('success', "Program “{$title}” deleted.");
    }

    private function validateProgram(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'qualification' => ['nullable', 'string', 'max:120'],
            'level' => ['nullable', 'string', 'max:20'],
            'hours' => ['required', 'integer', 'min:0', 'max:5000'],
            'fee' => ['required', 'integer', 'min:0'],
            'slots' => ['required', 'integer', 'min:0', 'max:500'],
            'active' => ['boolean'],
        ]);
    }
}
