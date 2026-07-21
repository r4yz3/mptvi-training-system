<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Program;
use App\Models\SubjectGrade;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    private function as(string $role): User
    {
        return User::role($role)->firstOrFail();
    }

    private function csv(string $body): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('data.csv', $body);
    }

    public function test_import_page_is_admin_registrar_only(): void
    {
        $this->actingAs($this->as('cashier'))->get('/import')->assertForbidden();
        $this->actingAs($this->as('admin'))->get('/import')->assertOk()
            ->assertInertia(fn ($p) => $p->component('Import/Index')->where('canTrainees', true));
    }

    public function test_trainee_template_downloads_as_xlsx(): void
    {
        $this->actingAs($this->as('admin'))->get('/import/trainees/template')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_trainee_csv_previews_then_imports_valid_rows(): void
    {
        $program = Program::create(['title' => 'Welding NC II', 'fee' => 1000, 'active' => true]);
        $admin = $this->as('admin');

        $csv = "Last name,First name,Middle name,Ext name,Sex,Birthdate,Contact,Barangay,City,Province,Education,Program,School year\n"
            . "Dela Cruz,Juan,Santos,,Male,2000-01-15,09171234567,Poblacion,Magsaysay,Davao del Sur,High School Graduate,Welding NC II,2026\n"
            . "Reyes,Ana,,,Female,,0918,Bulatukan,,,,Unknown Program,2026\n";      // program not found → error

        // Preview stores the file and flashes a per-row result.
        $this->actingAs($admin)->post('/import/trainees/preview', ['file' => $this->csv($csv)])
            ->assertRedirect();

        $preview = Session::get('import_preview');
        $this->assertSame(1, $preview['ok']);
        $this->assertSame(1, $preview['errors']);

        // Commit imports only the valid row.
        $this->actingAs($admin)->post('/import/trainees/commit', ['token' => $preview['token'], 'ext' => $preview['ext']])
            ->assertRedirect('/import');

        $this->assertSame(1, Applicant::count());
        $a = Applicant::first();
        $this->assertSame('DELA CRUZ', $a->last_name);        // uppercased, government-form style
        $this->assertSame($program->id, $a->program_id);
        $this->assertSame('Registered', $a->status);
    }

    public function test_grades_csv_imports_by_trainee_id_and_subject(): void
    {
        $program = Program::create(['title' => 'Welding NC II', 'fee' => 1000, 'active' => true]);
        $subject = $program->subjects()->create(['title' => 'Core Skills A', 'category' => 'Major', 'units' => 3]);
        $a = Applicant::create([
            'program_id' => $program->id, 'status' => 'In training', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '09',
        ]);
        $registrar = $this->as('registrar');

        $csv = "Trainee ID,Trainee,Program,Subject,Grade\n"
            . "{$a->id},Juan Cruz,Welding NC II,Core Skills A,1.75\n"
            . "{$a->id},Juan Cruz,Welding NC II,Core Skills A,9.9\n";  // bad grade → error

        $this->actingAs($registrar)->post('/import/grades/preview', ['file' => $this->csv($csv)])->assertRedirect();
        $preview = Session::get('import_preview');
        $this->assertSame(1, $preview['ok']);

        $this->actingAs($registrar)->post('/import/grades/commit', ['token' => $preview['token'], 'ext' => $preview['ext']])
            ->assertRedirect('/import');

        $grade = SubjectGrade::where('applicant_id', $a->id)->where('subject_id', $subject->id)->first();
        $this->assertNotNull($grade);
        $this->assertSame('1.75', (string) $grade->grade);
    }

    public function test_cashier_cannot_import(): void
    {
        $this->actingAs($this->as('cashier'))->post('/import/trainees/preview', ['file' => $this->csv("Last name\nX\n")])
            ->assertForbidden();
    }
}
