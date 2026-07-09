<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Assessment;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    public function index(Request $request): Response
    {
        $priority = ['For assessment' => 0, 'In training' => 1, 'Certified' => 2];

        $applicants = Applicant::query()
            ->with(['program:id,title,level', 'assessments'])
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
                'cert_number' => $a->cert_number,
                'last_result' => $a->assessments->first()?->result,
                // assessor printed on the certificate (override → recorded → default)
                'cert_assessor' => $a->cert_assessor,
                'assessor' => $this->effectiveAssessor($a),
            ]);

        return Inertia::render('Assessment/Index', [
            'applicants' => $applicants,
            'canAssess' => $request->user()->can('assess'),
            'canEditAssessor' => $request->user()->can('cert.assessor'),
            'defaultAssessor' => Setting::assessor(),
        ]);
    }

    /** In training → For assessment. */
    public function markForAssessment(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('assess'), 403);
        if ($applicant->status === 'In training') {
            $applicant->update(['status' => 'For assessment']);
        }

        return back()->with('success', "“{$applicant->display_name}” endorsed for assessment.");
    }

    public function record(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('assess'), 403);

        $data = $request->validate([
            'result' => ['required', Rule::in(['Competent', 'Not Yet Competent'])],
            'assessed_at' => ['required', 'date'],
            'assessor' => ['nullable', 'string', 'max:160'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $data['assessor'] = ($data['assessor'] ?? '') ?: (Setting::assessor() ?: null);

        Assessment::create([...$data, 'applicant_id' => $applicant->id, 'recorded_by' => $request->user()->id]);

        if ($data['result'] === 'Competent') {
            $applicant->update([
                'status' => 'Certified',
                'cert_number' => $applicant->cert_number ?: $this->nextCertNumber(),
                'certified_at' => $data['assessed_at'],
            ]);
        } else {
            $applicant->update(['status' => 'For assessment']);
        }

        return back()->with('success', "Assessment recorded: {$data['result']}.");
    }

    /** Set the assessor printed on a trainee's certificate (admin/secretary/registrar/coordinator). */
    public function updateAssessor(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($request->user()->can('cert.assessor'), 403);

        $data = $request->validate(['assessor' => ['nullable', 'string', 'max:160']]);
        $applicant->update(['cert_assessor' => $data['assessor'] !== '' ? $data['assessor'] : null]);

        return back()->with('success', "Certificate assessor updated for {$applicant->display_name}.");
    }

    /** Assessor used on the certificate: per-trainee override → recorded → configured default. */
    private function effectiveAssessor(Applicant $applicant): ?string
    {
        return $applicant->cert_assessor
            ?: ($applicant->assessments->firstWhere('result', 'Competent')?->assessor ?: (Setting::assessor() ?: null));
    }

    /** Printable National Certificate (A4 landscape) — only for certified trainees. */
    public function certificate(Applicant $applicant): \Illuminate\Contracts\View\View
    {
        abort_unless($applicant->status === 'Certified' && $applicant->cert_number, 404);
        $applicant->load(['program', 'assessments']);

        $issued = $applicant->certified_at;
        $assessment = $applicant->assessments->firstWhere('result', 'Competent');

        return view('certificates.print', [
            'a' => $applicant,
            'program' => $applicant->program,
            // Per-trainee override → the assessment's recorded assessor → the configured default.
            'assessor' => $applicant->cert_assessor ?: ($assessment?->assessor ?: Setting::assessor()),
            'issued' => $issued,
            // TESDA National Certificates are valid for 5 years from issuance.
            'validUntil' => $issued?->copy()->addYears(5),
            'signatories' => Setting::signatories(),
            'org' => Setting::institution(),
        ]);
    }

    private function nextCertNumber(): string
    {
        $year = now()->format('Y');
        $prefix = config('academic.cert_prefix', 'CK2') ?: 'CK2';
        $seq = Applicant::whereNotNull('cert_number')->count() + 1;

        do {
            $num = sprintf('%s-%s-%04d', $prefix, $year, $seq);
            $seq++;
        } while (Applicant::where('cert_number', $num)->exists());

        return $num;
    }
}
