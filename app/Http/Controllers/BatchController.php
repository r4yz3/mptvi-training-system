<?php

namespace App\Http\Controllers;

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
