<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    /** The note statuses staff may record per requirement. */
    public const STATUSES = ['Pending', 'Submitted', 'Not applicable'];

    /**
     * Record a typed note + status for one documentary requirement.
     * Note-only: no files are uploaded — staff just note what the applicant
     * presented (or why a document is missing / not applicable).
     */
    public function save(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('docs.verify'), 403);

        $data = $request->validate([
            'requirement_key' => ['required', 'integer'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $req = collect(config('requirements'))->firstWhere('key', $data['requirement_key']);
        abort_if($req === null, 422, 'Unknown requirement.');

        Document::updateOrCreate(
            ['applicant_id' => $applicant->id, 'requirement_key' => $data['requirement_key']],
            [
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
                'noted_by' => $request->user()->id,
            ],
        );

        return back()->with('success', "Saved: {$req['label']}.");
    }
}
