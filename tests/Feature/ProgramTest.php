<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramTest extends TestCase
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

    public function test_module_is_scoped_to_admin_secretary_coordinator(): void
    {
        $this->actingAs($this->as('coordinator'))->get('/programs')->assertOk();
        $this->actingAs($this->as('admin'))->get('/programs')->assertOk();
        $this->actingAs($this->as('cashier'))->get('/programs')->assertForbidden();
    }

    public function test_coordinator_can_create_program(): void
    {
        $this->actingAs($this->as('coordinator'))
            ->post('/programs', [
                'title' => 'Carpentry NC II', 'training_type' => 'school_based', 'level' => 'NC II', 'qualification' => 'Construction',
                'hours' => 232, 'fee' => 1000, 'slots' => 25, 'active' => true,
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('programs', ['title' => 'Carpentry NC II', 'hours' => 232, 'training_type' => 'school_based']);
    }

    public function test_community_based_program_is_forced_free(): void
    {
        $this->actingAs($this->as('coordinator'))
            ->post('/programs', [
                'title' => 'Financial Literacy Workshop', 'training_type' => 'community_based', 'level' => 'Non-NC',
                'qualification' => 'Soft Skills', 'hours' => 16, 'fee' => 500, 'slots' => 40, 'active' => true,
            ])
            ->assertRedirect();
        // Community-Based training never carries a fee, even if one is submitted.
        $this->assertDatabaseHas('programs', ['title' => 'Financial Literacy Workshop', 'training_type' => 'community_based', 'fee' => 0]);
    }

    public function test_training_type_is_required_and_validated(): void
    {
        $this->actingAs($this->as('coordinator'))
            ->post('/programs', [
                'title' => 'Bogus', 'training_type' => 'nonsense', 'hours' => 10, 'fee' => 0, 'slots' => 10,
            ])
            ->assertSessionHasErrors('training_type');
    }

    public function test_batch_end_date_is_auto_computed(): void
    {
        $program = Program::where('hours', '>', 0)->first(); // e.g. 268h
        $this->actingAs($this->as('coordinator'))
            ->post('/batches', [
                'program_id' => $program->id, 'code' => '2026-B', 'class_session' => 'Whole-day',
                'class_days' => 'Mon–Fri', 'capacity' => 20, 'start_date' => '2026-06-15', 'status' => 'Planned',
            ])
            ->assertRedirect();

        $batch = Batch::where('code', '2026-B')->first();
        $this->assertNotNull($batch->end_date);
        // Whole-day (8h) finishes sooner than the start date + a few weeks.
        $this->assertTrue($batch->end_date->gt($batch->start_date));
    }

    public function test_whole_day_finishes_before_morning(): void
    {
        $morning = Batch::computeEndDate('2026-06-15', 160, 'Morning', 'Mon–Fri');
        $wholeDay = Batch::computeEndDate('2026-06-15', 160, 'Whole-day', 'Mon–Fri');
        $this->assertTrue($wholeDay < $morning);
    }

    public function test_cannot_delete_program_with_applicants(): void
    {
        $program = Program::first();
        \App\Models\Applicant::create([
            'program_id' => $program->id, 'status' => 'Registered', 'active' => true,
            'last_name' => 'X', 'first_name' => 'Y', 'barangay' => 'Z', 'contact' => '09',
        ]);

        $this->actingAs($this->as('admin'))
            ->delete("/programs/{$program->id}")
            ->assertSessionHas('error');
        $this->assertDatabaseHas('programs', ['id' => $program->id]);
    }
}
