<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\CompetencyResult;
use App\Models\CompetencyUnit;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompetencyTest extends TestCase
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

    private function program(): Program
    {
        $p = Program::create(['title' => 'Test Qualification', 'level' => 'NC II', 'fee' => 1000, 'active' => true]);
        $p->competencyUnits()->createMany([
            ['title' => 'Weld carbon steel plates', 'type' => 'Core', 'sort' => 0],
            ['title' => 'Weld carbon steel pipes', 'type' => 'Core', 'sort' => 1],
        ]);

        return $p;
    }

    private function trainee(Program $program): Applicant
    {
        $batch = Batch::create([
            'program_id' => $program->id, 'code' => '2026-C', 'class_session' => 'Morning',
            'class_days' => 'Mon–Fri', 'capacity' => 20, 'status' => 'Ongoing',
        ]);

        return Applicant::create([
            'program_id' => $program->id, 'batch_id' => $batch->id,
            'status' => 'In training', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '0917',
        ]);
    }

    public function test_settings_page_is_admin_only_and_lists_units(): void
    {
        $this->program();

        $this->actingAs($this->as('coordinator'))->get('/settings/competencies')->assertForbidden();

        $this->actingAs($this->as('admin'))->get('/settings/competencies')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Settings/Competencies')
                ->has('programs', 1)
                ->where('programs.0.units', fn ($u) => count($u) === 2)
                ->has('types', 3));
    }

    public function test_admin_can_add_edit_and_remove_units(): void
    {
        $program = $this->program();
        $first = $program->competencyUnits()->first();

        $this->actingAs($this->as('admin'))->put("/settings/competencies/{$program->id}", [
            'units' => [
                ['id' => $first->id, 'code' => 'CORE1', 'title' => 'Weld plates (renamed)', 'type' => 'Core'],
                ['id' => null, 'code' => null, 'title' => 'Apply safety practices', 'type' => 'Common'],
                // the program's second Core unit is omitted → deleted
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $units = $program->competencyUnits()->get();
        $this->assertCount(2, $units);
        $this->assertSame('Weld plates (renamed)', $units->firstWhere('id', $first->id)->title);
        $this->assertSame('CORE1', $units->firstWhere('id', $first->id)->code);
        $this->assertNotNull($units->firstWhere('title', 'Apply safety practices'));
    }

    public function test_coordinator_can_rate_units_and_summary_reflects(): void
    {
        $program = $this->program();
        $a = $this->trainee($program);
        [$u1, $u2] = $program->competencyUnits()->get()->all();

        $this->actingAs($this->as('coordinator'))->put("/applicants/{$a->id}/competency", [
            'rated_at' => '2026-07-01',
            'ratings' => [
                ['unit_id' => $u1->id, 'result' => 'Competent'],
                ['unit_id' => $u2->id, 'result' => 'Not Yet Competent'],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $summary = $a->fresh()->competencySummary();
        $this->assertSame(2, $summary['total']);
        $this->assertSame(1, $summary['competent']);
        $this->assertFalse($summary['complete']);
    }

    public function test_all_units_competent_completes_the_trainee(): void
    {
        $program = $this->program();
        $a = $this->trainee($program);
        $units = $program->competencyUnits()->get();

        $this->actingAs($this->as('coordinator'))->put("/applicants/{$a->id}/competency", [
            'rated_at' => '2026-07-01',
            'ratings' => $units->map(fn ($u) => ['unit_id' => $u->id, 'result' => 'Competent'])->all(),
        ])->assertRedirect();

        $a->refresh();
        $this->assertTrue($a->competencySummary()['complete']);
        $this->assertSame('Completed', $a->trainee_status);
    }

    public function test_blank_result_clears_a_rating(): void
    {
        $program = $this->program();
        $a = $this->trainee($program);
        $u1 = $program->competencyUnits()->first();

        CompetencyResult::create(['applicant_id' => $a->id, 'competency_unit_id' => $u1->id, 'result' => 'Competent', 'rated_at' => '2026-07-01']);

        $this->actingAs($this->as('coordinator'))->put("/applicants/{$a->id}/competency", [
            'rated_at' => '2026-07-02',
            'ratings' => [['unit_id' => $u1->id, 'result' => null]],
        ])->assertRedirect();

        $this->assertSame(0, $a->competencyResults()->count());
    }

    public function test_units_from_another_program_are_ignored(): void
    {
        $program = $this->program();
        $a = $this->trainee($program);
        $foreign = CompetencyUnit::create(['program_id' => Program::create(['title' => 'Other', 'fee' => 0])->id, 'title' => 'X', 'type' => 'Core']);

        $this->actingAs($this->as('coordinator'))->put("/applicants/{$a->id}/competency", [
            'rated_at' => '2026-07-01',
            'ratings' => [['unit_id' => $foreign->id, 'result' => 'Competent']],
        ])->assertRedirect();

        $this->assertSame(0, $a->competencyResults()->count());
    }

    public function test_rating_requires_assess_and_valid_result(): void
    {
        $program = $this->program();
        $a = $this->trainee($program);
        $u1 = $program->competencyUnits()->first();

        // cashier lacks the training module entirely
        $this->actingAs($this->as('cashier'))->put("/applicants/{$a->id}/competency", [
            'rated_at' => '2026-07-01', 'ratings' => [],
        ])->assertForbidden();

        // invalid result value rejected
        $this->actingAs($this->as('coordinator'))->put("/applicants/{$a->id}/competency", [
            'rated_at' => '2026-07-01',
            'ratings' => [['unit_id' => $u1->id, 'result' => 'Maybe']],
        ])->assertSessionHasErrors('ratings.0.result');
    }

    public function test_profile_exposes_competency_info(): void
    {
        $program = $this->program();
        $a = $this->trainee($program);

        $this->actingAs($this->as('coordinator'))->get("/applicants/{$a->id}")
            ->assertInertia(fn (Assert $p) => $p
                ->where('competencyInfo.total', 2)
                ->where('competencyInfo.complete', false)
                ->has('competencyInfo.units', 2));
    }

    public function test_report_card_renders_for_a_trainee(): void
    {
        $program = $this->program();
        $a = $this->trainee($program);
        $u1 = $program->competencyUnits()->first();
        CompetencyResult::create(['applicant_id' => $a->id, 'competency_unit_id' => $u1->id, 'result' => 'Competent', 'rated_at' => '2026-07-01']);

        // Competency Achievement Record (per trainee) — moved off the batch module.
        $this->actingAs($this->as('coordinator'))->get("/applicants/{$a->id}/report-card")
            ->assertOk()
            ->assertSee('COMPETENCY ACHIEVEMENT RECORD', false)
            ->assertSee('Juan Cruz');
        $this->actingAs($this->as('cashier'))->get("/applicants/{$a->id}/report-card")->assertForbidden();
    }
}
