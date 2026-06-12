<?php

namespace App\Http\Controllers;

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
            ->with(['batches' => fn ($q) => $q->orderByDesc('id')])
            ->orderBy('title')
            ->get();

        return Inertia::render('Programs/Index', [
            'programs' => $programs,
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
