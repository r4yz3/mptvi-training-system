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
            ->withCount('applicants')
            ->with(['applicants' => fn ($q) => $q
                ->orderBy('last_name')->orderBy('first_name')
                ->get(['id', 'first_name', 'middle_name', 'last_name', 'ext_name', 'program_id', 'status', 'active'])])
            ->orderBy('title')
            ->get()
            ->map(fn (Program $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'qualification' => $p->qualification,
                'training_type' => $p->training_type,
                'level' => $p->level,
                'hours' => $p->hours,
                'fee' => $p->fee,
                'slots' => $p->slots,
                'active' => $p->active,
                'applicants_count' => $p->applicants_count,
                // The trainees enrolled in this program (what the institute wants to see).
                'trainees' => $p->applicants->map(fn (Applicant $a) => [
                    'id' => $a->id,
                    'name' => $a->display_name,
                    'status' => $a->status,
                    'active' => $a->active,
                ])->values(),
            ]);

        return Inertia::render('Programs/Index', [
            'programs' => $programs,
            'options' => [
                'training_types' => [
                    ['value' => Program::SCHOOL_BASED, 'label' => 'School-Based (fee, months-long)'],
                    ['value' => Program::COMMUNITY_BASED, 'label' => 'Community-Based (free soft-skills)'],
                ],
                'levels' => ['NC I', 'NC II', 'NC III', 'NC IV', 'Non-NC'],
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
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'qualification' => ['nullable', 'string', 'max:120'],
            'training_type' => ['required', 'in:' . Program::SCHOOL_BASED . ',' . Program::COMMUNITY_BASED],
            'level' => ['nullable', 'string', 'max:20'],
            'hours' => ['required', 'integer', 'min:0', 'max:5000'],
            'fee' => ['required', 'integer', 'min:0'],
            'slots' => ['required', 'integer', 'min:0', 'max:500'],
            'active' => ['boolean'],
        ]);

        // Community-based training is free soft-skills — never carries a fee.
        if ($data['training_type'] === Program::COMMUNITY_BASED) {
            $data['fee'] = 0;
        }

        return $data;
    }
}
