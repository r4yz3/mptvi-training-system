<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Program;
use App\Models\Subject;
use App\Models\SubjectGrade;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GradesTest extends TestCase
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

    /** Program with 2 Major (3+5 units) and 1 Minor (2 units) subject. */
    private function program(): Program
    {
        $p = Program::create(['title' => 'Test Program', 'level' => 'NC II', 'fee' => 1000, 'active' => true]);
        $p->subjects()->createMany([
            ['title' => 'Core Skills A', 'category' => 'Major', 'units' => 3, 'sort' => 0],
            ['title' => 'Core Skills B', 'category' => 'Major', 'units' => 5, 'sort' => 1],
            ['title' => 'Communication', 'category' => 'Minor', 'units' => 2, 'sort' => 2],
        ]);

        return $p;
    }

    private function trainee(Program $program): Applicant
    {
        return Applicant::create([
            'program_id' => $program->id, 'status' => 'In training', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '0917',
        ]);
    }

    public function test_settings_page_is_admin_only_and_lists_subjects(): void
    {
        $this->program();

        $this->actingAs($this->as('cashier'))->get('/settings/subjects')->assertForbidden();

        $this->actingAs($this->as('admin'))->get('/settings/subjects')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Settings/Subjects')
                ->has('programs', 1)
                ->where('programs.0.subjects', fn ($s) => count($s) === 3)
                ->has('categories', 2));
    }

    public function test_admin_can_add_edit_and_remove_subjects(): void
    {
        $program = $this->program();
        $first = $program->subjects()->first();

        $this->actingAs($this->as('admin'))->put("/settings/subjects/{$program->id}", [
            'subjects' => [
                ['id' => $first->id, 'code' => 'MAJ1', 'title' => 'Core Skills A (renamed)', 'category' => 'Major', 'units' => 4],
                ['id' => null, 'code' => null, 'title' => 'Values', 'category' => 'Minor', 'units' => 1],
                // the other two subjects are omitted → deleted
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $subjects = $program->subjects()->get();
        $this->assertCount(2, $subjects);
        $this->assertSame('Core Skills A (renamed)', $subjects->firstWhere('id', $first->id)->title);
        $this->assertSame(4, $subjects->firstWhere('id', $first->id)->units);
    }

    public function test_registrar_records_grades_and_gwa_is_unit_weighted(): void
    {
        $program = $this->program();
        $a = $this->trainee($program);
        [$maj1, $maj2, $min1] = $program->subjects()->get()->all();

        // Major: 1.50 (3u) + 2.00 (5u); Minor: 1.00 (2u).
        $this->actingAs($this->as('registrar'))->put("/applicants/{$a->id}/grades", [
            'graded_at' => '2026-08-01',
            'grades' => [
                ['subject_id' => $maj1->id, 'grade' => 1.50],
                ['subject_id' => $maj2->id, 'grade' => 2.00],
                ['subject_id' => $min1->id, 'grade' => 1.00],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $summary = $a->fresh()->load('program.subjects', 'subjectGrades')->gradeSummary();
        // Major GWA = (1.50*3 + 2.00*5) / 8 = 14.5/8 = 1.81
        $this->assertSame(1.81, $summary['major_gwa']);
        // Minor GWA = 1.00
        $this->assertSame(1.0, $summary['minor_gwa']);
        // Overall = (4.5 + 10 + 2) / 10 = 16.5/10 = 1.65
        $this->assertSame(1.65, $summary['gwa']);
        $this->assertTrue($summary['complete']);
        $this->assertSame('Passed', $summary['remark']);
        $this->assertSame('Completed', $a->fresh()->trainee_status);
    }

    public function test_a_failing_grade_marks_the_trainee_failed(): void
    {
        $program = $this->program();
        $a = $this->trainee($program);
        [$maj1, $maj2, $min1] = $program->subjects()->get()->all();

        $this->actingAs($this->as('registrar'))->put("/applicants/{$a->id}/grades", [
            'graded_at' => '2026-08-01',
            'grades' => [
                ['subject_id' => $maj1->id, 'grade' => 2.00],
                ['subject_id' => $maj2->id, 'grade' => 5.00], // failed
                ['subject_id' => $min1->id, 'grade' => 1.50],
            ],
        ])->assertRedirect();

        $this->assertSame('Failed', $a->fresh()->load('program.subjects', 'subjectGrades')->gradeSummary()['remark']);
    }

    public function test_blank_grade_clears_it_and_foreign_subjects_are_ignored(): void
    {
        $program = $this->program();
        $a = $this->trainee($program);
        $maj1 = $program->subjects()->first();
        SubjectGrade::create(['applicant_id' => $a->id, 'subject_id' => $maj1->id, 'grade' => 2.00, 'graded_at' => '2026-08-01']);

        $foreign = Subject::create(['program_id' => Program::create(['title' => 'Other', 'fee' => 0])->id, 'title' => 'X', 'category' => 'Major', 'units' => 3]);

        $this->actingAs($this->as('registrar'))->put("/applicants/{$a->id}/grades", [
            'graded_at' => '2026-08-02',
            'grades' => [
                ['subject_id' => $maj1->id, 'grade' => null],       // clears
                ['subject_id' => $foreign->id, 'grade' => 1.00],    // ignored (wrong program)
            ],
        ])->assertRedirect();

        $this->assertSame(0, $a->subjectGrades()->count());
    }

    public function test_grades_require_assess_cap_and_valid_range(): void
    {
        $program = $this->program();
        $a = $this->trainee($program);
        $maj1 = $program->subjects()->first();

        $this->actingAs($this->as('cashier'))->put("/applicants/{$a->id}/grades", [
            'graded_at' => '2026-08-01', 'grades' => [],
        ])->assertForbidden();

        $this->actingAs($this->as('registrar'))->put("/applicants/{$a->id}/grades", [
            'graded_at' => '2026-08-01',
            'grades' => [['subject_id' => $maj1->id, 'grade' => 6.00]], // out of 1..5
        ])->assertSessionHasErrors('grades.0.grade');
    }

    public function test_profile_exposes_grade_info_and_slip_prints(): void
    {
        $program = $this->program();
        $a = $this->trainee($program);

        $this->actingAs($this->as('registrar'))->get("/applicants/{$a->id}")
            ->assertInertia(fn (Assert $p) => $p
                ->where('gradeInfo.total', 3)
                ->where('gradeInfo.remark', 'In progress')
                ->has('gradeInfo.subjects', 3));

        $this->actingAs($this->as('registrar'))->get("/applicants/{$a->id}/grade-slip")
            ->assertOk()->assertSee('REPORT OF GRADES', false)->assertSee('Juan Cruz');
        $this->actingAs($this->as('cashier'))->get("/applicants/{$a->id}/grade-slip")->assertForbidden();
    }
}
