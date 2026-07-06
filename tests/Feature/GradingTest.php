<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GradingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(ProgramSeeder::class);
    }

    private function as(string $role): User
    {
        return User::role($role)->firstOrFail();
    }

    private function makeTrainee(): Applicant
    {
        $batch = Batch::create([
            'program_id' => Program::first()->id, 'code' => '2026-G', 'class_session' => 'Morning',
            'class_days' => 'Mon–Fri', 'capacity' => 25, 'status' => 'Ongoing',
        ]);

        return Applicant::create([
            'program_id' => Program::first()->id, 'batch_id' => $batch->id,
            'status' => 'In training', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '0917',
        ]);
    }

    public function test_settings_page_is_admin_only(): void
    {
        $this->actingAs($this->as('registrar'))->get('/settings/grading')->assertForbidden();

        $this->actingAs($this->as('admin'))->get('/settings/grading')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Settings/Grading')
                ->has('components', 3)
                ->where('passing', 75));
    }

    public function test_admin_can_edit_components_and_passing_grade(): void
    {
        $this->actingAs($this->as('admin'))->put('/settings/grading', [
            'components' => [
                ['key' => 'written', 'label' => 'Written test', 'weight' => 40],
                ['key' => '', 'label' => 'Project output', 'weight' => 60],
            ],
            'passing' => 80,
        ])->assertRedirect()->assertSessionHas('success');

        Setting::applyConfigOverrides();

        $components = config('grading.components');
        $this->assertCount(2, $components);
        $this->assertSame('project_output', $components[1]['key']);
        $this->assertSame(80, (int) config('grading.passing'));
    }

    public function test_weights_must_total_100(): void
    {
        $this->actingAs($this->as('admin'))->put('/settings/grading', [
            'components' => [
                ['key' => 'written', 'label' => 'Written', 'weight' => 40],
                ['key' => 'practical', 'label' => 'Practical', 'weight' => 40],
            ],
            'passing' => 75,
        ])->assertSessionHasErrors('components');
    }

    public function test_coordinator_can_grade_and_final_is_computed(): void
    {
        $a = $this->makeTrainee();

        // Defaults: written 30 / practical 50 / attendance 20 → 80*0.3 + 90*0.5 + 100*0.2 = 89
        $this->actingAs($this->as('coordinator'))
            ->put("/training/{$a->id}/grades", [
                'scores' => ['written' => 80, 'practical' => 90, 'attendance' => 100],
            ])
            ->assertRedirect()->assertSessionHas('success');

        $summary = $a->fresh()->gradeSummary();
        $this->assertSame(89.0, $summary['final']);
        $this->assertSame('Passed', $summary['remark']);
    }

    public function test_incomplete_scores_yield_no_final_and_failing_scores_fail(): void
    {
        $a = $this->makeTrainee();

        $this->actingAs($this->as('coordinator'))
            ->put("/training/{$a->id}/grades", ['scores' => ['written' => 80]]);
        $this->assertSame('Incomplete', $a->fresh()->gradeSummary()['remark']);

        $this->actingAs($this->as('coordinator'))
            ->put("/training/{$a->id}/grades", [
                'scores' => ['written' => 60, 'practical' => 65, 'attendance' => 70],
            ]);
        $summary = $a->fresh()->gradeSummary();
        $this->assertSame(64.5, $summary['final']);
        $this->assertSame('Failed', $summary['remark']);
    }

    public function test_grading_requires_assess_cap_and_valid_scores(): void
    {
        $a = $this->makeTrainee();

        // cashier lacks the training module entirely
        $this->actingAs($this->as('cashier'))
            ->put("/training/{$a->id}/grades", ['scores' => ['written' => 80]])
            ->assertForbidden();

        // out-of-range score rejected
        $this->actingAs($this->as('coordinator'))
            ->put("/training/{$a->id}/grades", ['scores' => ['written' => 140]])
            ->assertSessionHasErrors('scores.written');
    }

    public function test_roster_and_profile_expose_grade_summary(): void
    {
        $a = $this->makeTrainee();
        $this->actingAs($this->as('coordinator'))
            ->put("/training/{$a->id}/grades", [
                'scores' => ['written' => 80, 'practical' => 90, 'attendance' => 100],
            ]);

        $this->actingAs($this->as('coordinator'))->get("/training/{$a->batch_id}")
            ->assertInertia(fn (Assert $p) => $p
                ->where('roster.0.grade.final', 89)
                ->where('roster.0.grade.remark', 'Passed')
                ->has('gradeComponents', 3));

        $this->actingAs($this->as('coordinator'))->get("/applicants/{$a->id}")
            ->assertInertia(fn (Assert $p) => $p
                ->where('gradeInfo.summary.final', 89)
                ->where('gradeInfo.summary.remark', 'Passed'));
    }
}
