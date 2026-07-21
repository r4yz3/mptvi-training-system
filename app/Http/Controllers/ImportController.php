<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Program;
use App\Models\Subject;
use App\Models\SubjectGrade;
use App\Support\SpreadsheetReader;
use App\Support\Xlsx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Excel/CSV bulk import for trainees and grades. Upload → validate every row →
 * preview OK/error → confirm imports only the valid rows. A template can be
 * downloaded so the columns are always right.
 */
class ImportController extends Controller
{
    private const TRAINEE_COLS = ['Last name', 'First name', 'Middle name', 'Ext name', 'Sex', 'Birthdate', 'Contact', 'Barangay', 'City', 'Province', 'Education', 'Program', 'School year'];

    private const GRADE_COLS = ['Trainee ID', 'Trainee', 'Program', 'Subject', 'Grade'];

    public function index(Request $request): Response
    {
        return Inertia::render('Import/Index', [
            'canTrainees' => $request->user()->can('applicant.create'),
            'canGrades' => $request->user()->can('assess'),
            'programs' => Program::orderBy('title')->get(['id', 'title'])
                ->map(fn (Program $p) => ['id' => $p->id, 'title' => $p->title]),
        ]);
    }

    /** Download an .xlsx template for the chosen import type. */
    public function template(Request $request, string $type): BinaryFileResponse
    {
        if ($type === 'grades') {
            abort_unless($request->user()->can('assess'), 403);
            $program = Program::with('subjects')->findOrFail($request->integer('program'));
            $rows = [];
            $learners = $program->applicants()->where('active', true)->orderBy('last_name')->get();
            foreach ($learners as $a) {
                foreach ($program->subjects as $s) {
                    $rows[] = [$a->id, $a->display_name, $program->title, $s->title, ''];
                }
            }

            return Xlsx::download('grades-template.xlsx', self::GRADE_COLS, $rows, 'Grades');
        }

        abort_unless($request->user()->can('applicant.create'), 403);
        $sample = ['Dela Cruz', 'Juan', 'Santos', '', 'Male', '2000-01-15', '09171234567', 'Poblacion', 'Magsaysay', 'Davao del Sur', 'High School Graduate', Program::value('title') ?? 'Program title', (string) now()->year];

        return Xlsx::download('trainees-template.xlsx', self::TRAINEE_COLS, [$sample], 'Trainees');
    }

    /** Parse + validate the uploaded file; store it and return a per-row preview. */
    public function preview(Request $request, string $type): RedirectResponse
    {
        $this->authorizeType($request, $type);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:8192']]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $token = Str::uuid()->toString();
        $stored = $request->file('file')->storeAs('imports', "{$token}.{$ext}", 'local');
        $rows = SpreadsheetReader::rows(Storage::disk('local')->path($stored), $ext);

        $result = $type === 'grades' ? $this->checkGrades($rows) : $this->checkTrainees($rows);

        return back()->with('import_preview', [
            'type' => $type,
            'token' => $token,
            'ext' => $ext,
            'rows' => $result['rows'],
            'ok' => $result['ok'],
            'errors' => $result['errors'],
        ]);
    }

    /** Import the valid rows of a previously previewed file, then delete it. */
    public function commit(Request $request, string $type): RedirectResponse
    {
        $this->authorizeType($request, $type);
        $data = $request->validate([
            'token' => ['required', 'string'],
            'ext' => ['required', 'in:csv,txt,xlsx'],
        ]);

        $path = "imports/{$data['token']}.{$data['ext']}";
        abort_unless(Storage::disk('local')->exists($path), 404);

        $rows = SpreadsheetReader::rows(Storage::disk('local')->path($path), $data['ext']);
        $imported = $type === 'grades' ? $this->importGrades($rows) : $this->importTrainees($rows);
        Storage::disk('local')->delete($path);

        return redirect('/import')->with('success', "{$imported['created']} row(s) imported, {$imported['skipped']} skipped.");
    }

    private function authorizeType(Request $request, string $type): void
    {
        abort_unless(in_array($type, ['trainees', 'grades'], true), 404);
        abort_unless($request->user()->can($type === 'grades' ? 'assess' : 'applicant.create'), 403);
    }

    /* ------------------------------- Trainees -------------------------------- */

    private function checkTrainees(array $rows): array
    {
        $programs = Program::pluck('id', 'title')->mapWithKeys(fn ($id, $t) => [mb_strtolower(trim($t)) => $id]);
        $out = [];
        $ok = 0;
        $errors = 0;

        foreach ($rows as $i => $r) {
            $err = [];
            foreach (['Last name', 'First name', 'Barangay', 'Contact', 'Program'] as $req) {
                if (($r[$req] ?? '') === '') {
                    $err[] = "$req is required";
                }
            }
            if (($r['Program'] ?? '') !== '' && ! $programs->has(mb_strtolower(trim($r['Program'])))) {
                $err[] = "Program “{$r['Program']}” not found";
            }
            if (($r['Birthdate'] ?? '') !== '' && ! $this->parseDate($r['Birthdate'])) {
                $err[] = 'Birthdate is not a valid date';
            }

            $out[] = ['line' => $i + 2, 'data' => $r, 'ok' => empty($err), 'error' => implode('; ', $err)];
            empty($err) ? $ok++ : $errors++;
        }

        return ['rows' => $out, 'ok' => $ok, 'errors' => $errors];
    }

    private function importTrainees(array $rows): array
    {
        $programs = Program::pluck('id', 'title')->mapWithKeys(fn ($id, $t) => [mb_strtolower(trim($t)) => $id]);
        $created = 0;
        $skipped = 0;

        foreach ($rows as $r) {
            $programId = $programs->get(mb_strtolower(trim($r['Program'] ?? '')));
            $required = ($r['Last name'] ?? '') !== '' && ($r['First name'] ?? '') !== ''
                && ($r['Barangay'] ?? '') !== '' && ($r['Contact'] ?? '') !== '' && $programId;
            if (! $required) {
                $skipped++;

                continue;
            }

            $up = fn ($v) => mb_strtoupper(trim((string) ($v ?? '')), 'UTF-8');
            $birthdate = $this->parseDate($r['Birthdate'] ?? '');

            Applicant::create([
                'last_name' => $up($r['Last name']),
                'first_name' => $up($r['First name']),
                'middle_name' => $up($r['Middle name'] ?? ''),
                'ext_name' => $up($r['Ext name'] ?? ''),
                'sex' => in_array($r['Sex'] ?? '', config('lpf.sex'), true) ? $r['Sex'] : 'Male',
                'birthdate' => $birthdate,
                'age' => $birthdate ? $birthdate->age : null,
                'contact' => trim((string) $r['Contact']),
                'barangay' => $up($r['Barangay']),
                'city' => $up($r['City'] ?? 'Magsaysay') ?: 'MAGSAYSAY',
                'province' => $up($r['Province'] ?? 'Davao del Sur') ?: 'DAVAO DEL SUR',
                'education' => trim((string) ($r['Education'] ?? '')) ?: null,
                'program_id' => $programId,
                'school_year' => trim((string) ($r['School year'] ?? '')) ?: (string) config('academic.school_year', now()->year),
                'status' => 'Registered',
                'active' => true,
                'registered_at' => now(),
            ]);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /* -------------------------------- Grades --------------------------------- */

    private function checkGrades(array $rows): array
    {
        $out = [];
        $ok = 0;
        $errors = 0;

        foreach ($rows as $i => $r) {
            $err = [];
            $grade = trim((string) ($r['Grade'] ?? ''));
            if ($grade === '') {
                // Blank grade rows are simply not imported (no error).
                $out[] = ['line' => $i + 2, 'data' => $r, 'ok' => false, 'error' => 'No grade — skipped'];
                $errors++;

                continue;
            }
            $id = (int) ($r['Trainee ID'] ?? 0);
            $applicant = $id ? Applicant::find($id) : null;
            if (! $applicant) {
                $err[] = "Trainee ID {$id} not found";
            }
            $subject = $applicant ? $this->matchSubject($applicant, $r['Subject'] ?? '') : null;
            if ($applicant && ! $subject) {
                $err[] = "Subject “{$r['Subject']}” not in the trainee's program";
            }
            if (! is_numeric($grade) || $grade < 1 || $grade > 5) {
                $err[] = 'Grade must be 1.00–5.00';
            }

            $out[] = ['line' => $i + 2, 'data' => $r, 'ok' => empty($err), 'error' => implode('; ', $err)];
            empty($err) ? $ok++ : $errors++;
        }

        return ['rows' => $out, 'ok' => $ok, 'errors' => $errors];
    }

    private function importGrades(array $rows): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($rows as $r) {
            $grade = trim((string) ($r['Grade'] ?? ''));
            $applicant = ($id = (int) ($r['Trainee ID'] ?? 0)) ? Applicant::find($id) : null;
            $subject = $applicant ? $this->matchSubject($applicant, $r['Subject'] ?? '') : null;

            if ($grade === '' || ! $applicant || ! $subject || ! is_numeric($grade) || $grade < 1 || $grade > 5) {
                $skipped++;

                continue;
            }

            SubjectGrade::updateOrCreate(
                ['applicant_id' => $applicant->id, 'subject_id' => $subject->id],
                ['grade' => round((float) $grade, 2), 'graded_at' => now()->toDateString(), 'graded_by' => request()->user()->id],
            );
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function matchSubject(Applicant $applicant, string $needle): ?Subject
    {
        $needle = mb_strtolower(trim($needle));

        return $applicant->program?->subjects()->get()->first(fn (Subject $s) => mb_strtolower($s->title) === $needle
            || ($s->code && mb_strtolower($s->code) === $needle));
    }

    private function parseDate(string $value): ?Carbon
    {
        try {
            return $value !== '' ? Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
