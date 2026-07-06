<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BatchController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('program.manage'), 403);
        $data = $this->validateBatch($request);
        $data['end_date'] = $this->endDate($data);
        Batch::create($data);

        return back()->with('success', "Batch “{$data['code']}” created.");
    }

    public function update(Request $request, Batch $batch): RedirectResponse
    {
        abort_unless($request->user()->can('program.manage'), 403);
        $data = $this->validateBatch($request);
        $data['end_date'] = $this->endDate($data);
        $batch->update($data);

        return back()->with('success', "Batch “{$batch->code}” updated.");
    }

    public function destroy(Request $request, Batch $batch): RedirectResponse
    {
        abort_unless($request->user()->can('program.manage'), 403);
        if ($batch->applicants()->exists()) {
            return back()->with('error', 'Cannot delete a batch that has assigned learners.');
        }
        $code = $batch->code;
        $batch->delete();

        return back()->with('success', "Batch “{$code}” deleted.");
    }

    /** Add a learner to this batch. Cross-program adds must be explicit (move_program). */
    public function assign(Request $request, Batch $batch): RedirectResponse
    {
        abort_unless($request->user()->can('program.manage'), 403);

        $data = $request->validate([
            'applicant_id' => ['required', 'integer', 'exists:applicants,id'],
            'move_program' => ['nullable', 'boolean'],
        ]);
        $applicant = Applicant::findOrFail($data['applicant_id']);

        if (! $applicant->active || $applicant->status === 'Disqualified') {
            return back()->with('error', 'Only active, non-disqualified learners can be added to a batch.');
        }
        if ($applicant->batch_id === $batch->id) {
            return back()->with('error', "“{$applicant->display_name}” is already in batch {$batch->code}.");
        }
        if (in_array($batch->status, ['Closed', 'Completed'], true)) {
            return back()->with('error', "Batch {$batch->code} is {$batch->status} — learners can no longer be added.");
        }
        if ($batch->capacity > 0 && $batch->applicants()->count() >= $batch->capacity) {
            return back()->with('error', "Batch {$batch->code} is full ({$batch->capacity} slots).");
        }

        $update = ['batch_id' => $batch->id];
        if ($applicant->program_id !== $batch->program_id) {
            if (! $request->boolean('move_program')) {
                return back()->with('error', "“{$applicant->display_name}” belongs to a different program.");
            }
            $update['program_id'] = $batch->program_id;
        }
        $applicant->update($update);

        return back()->with('success', "“{$applicant->display_name}” added to batch {$batch->code}.");
    }

    /** Remove a learner from this batch (keeps their program and pipeline status). */
    public function unassign(Request $request, Batch $batch, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('program.manage'), 403);

        if ($applicant->batch_id !== $batch->id) {
            return back()->with('error', "“{$applicant->display_name}” is not in batch {$batch->code}.");
        }
        $applicant->update(['batch_id' => null]);

        return back()->with('success', "“{$applicant->display_name}” removed from batch {$batch->code}.");
    }

    private function endDate(array $data): ?string
    {
        $hours = Program::find($data['program_id'])?->hours ?? 0;

        return Batch::computeEndDate($data['start_date'] ?? null, $hours, $data['class_session'], $data['class_days']);
    }

    private function validateBatch(Request $request): array
    {
        return $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'code' => ['required', 'string', 'max:40'],
            'class_session' => ['required', Rule::in(['Morning', 'Afternoon', 'Whole-day'])],
            'class_days' => ['required', Rule::in(['Mon–Fri', 'MWF', 'Tue-Thu', 'Mon-Sat', 'Sat'])],
            'school_year' => ['nullable', 'string', 'max:20'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'trainer' => ['nullable', 'string', 'max:160'],
            'venue' => ['nullable', 'string', 'max:160'],
            'start_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['Planned', 'Open', 'Ongoing', 'Closed', 'Completed'])],
        ]);
    }
}
