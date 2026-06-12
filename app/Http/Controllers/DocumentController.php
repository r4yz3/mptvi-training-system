<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Document;
use App\Models\DocumentAudit;
use App\Models\DocumentFile;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /** Upload a file against a requirement (uploadable items only). */
    public function upload(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('docs.verify'), 403);

        $data = $request->validate([
            'requirement_key' => ['required', 'integer'],
            // Accept one or many files in a single upload (e.g. 2×2 = 2 pcs, PSA = 2 pcs).
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['file', 'max:8192', 'mimes:jpg,jpeg,png,pdf,webp'],
        ]);

        $req = $this->req($data['requirement_key']);
        abort_if($req === null || $req['physical'], 422, 'Not an uploadable requirement.');

        $document = Document::firstOrCreate(
            ['applicant_id' => $applicant->id, 'requirement_key' => $req['key']],
            ['status' => 'Pending'],
        );

        $names = [];
        foreach ($request->file('files') as $upload) {
            // Compress scanned images (birth certs, IDs) before storing; PDFs pass through untouched.
            ImageOptimizer::uploaded($upload, 2000, 85);

            // PRIVATE disk — never web-served. DB stores the path only.
            $path = $upload->store("documents/{$applicant->id}", 'local');

            $file = $document->files()->create([
                'path' => $path,
                'original_name' => $upload->getClientOriginalName(),
                'mime' => $upload->getMimeType(),
                'size' => $upload->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);
            $names[] = $upload->getClientOriginalName();
            $this->audit($document->id, $request->user()->id, 'upload', $upload->getClientOriginalName(), $file->id);
        }

        // New upload resets a previously-rejected/verified item to Submitted for re-review.
        $document->update(['status' => 'Submitted', 'reject_reason' => null, 'verified_at' => null, 'verified_by' => null]);

        $n = count($names);

        return back()->with('success', $n === 1 ? "Uploaded “{$names[0]}”." : "Uploaded {$n} files.");
    }

    public function verify(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user()->can('docs.verify'), 403);

        $document->update([
            'status' => 'Verified',
            'reject_reason' => null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);
        $this->audit($document->id, $request->user()->id, 'verify');

        return back()->with('success', 'Document verified.');
    }

    public function reject(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user()->can('docs.verify'), 403);

        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $document->update([
            'status' => 'Rejected',
            'reject_reason' => $data['reason'],
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);
        $this->audit($document->id, $request->user()->id, 'reject', $data['reason']);

        return back()->with('success', 'Document rejected.');
    }

    /** Physical supplies (Brown Envelope/Folder): mark received / un-received. */
    public function togglePhysical(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('docs.verify'), 403);

        $key = (int) $request->input('requirement_key');
        $req = $this->req($key);
        abort_if($req === null || ! $req['physical'], 422, 'Not a physical requirement.');

        $document = Document::firstOrCreate(
            ['applicant_id' => $applicant->id, 'requirement_key' => $key],
            ['status' => 'Pending'],
        );

        $received = $document->status !== 'Verified';
        $document->update([
            'status' => $received ? 'Verified' : 'Pending',
            'verified_by' => $received ? $request->user()->id : null,
            'verified_at' => $received ? now() : null,
        ]);
        $this->audit($document->id, $request->user()->id, $received ? 'received' : 'unreceived');

        return back()->with('success', $received ? "Marked received: {$req['label']}." : "Unmarked: {$req['label']}.");
    }

    /** Authenticated, audited file access — the ONLY way document files are served. */
    public function download(Request $request, DocumentFile $file): StreamedResponse
    {
        abort_unless($request->user()->can('pii.view'), 403);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        $this->audit($file->document_id, $request->user()->id, 'download', $file->original_name, $file->id);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function destroyFile(Request $request, DocumentFile $file): RedirectResponse
    {
        abort_unless($request->user()->can('docs.verify'), 403);

        $document = $file->document;
        Storage::disk('local')->delete($file->path);
        $name = $file->original_name;
        $file->delete();
        $this->audit($document->id, $request->user()->id, 'delete', $name);

        if ($document->files()->count() === 0) {
            $document->update(['status' => 'Pending', 'verified_at' => null, 'verified_by' => null]);
        }

        return back()->with('success', "Removed “{$name}”.");
    }

    // ---- helpers ----

    private function req(int $key): ?array
    {
        return collect(config('requirements'))->firstWhere('key', $key);
    }

    private function audit(?int $documentId, int $userId, string $action, ?string $detail = null, ?int $fileId = null): void
    {
        DocumentAudit::create([
            'document_id' => $documentId,
            'document_file_id' => $fileId,
            'user_id' => $userId,
            'action' => $action,
            'detail' => $detail,
            'created_at' => now(),
        ]);
    }
}
