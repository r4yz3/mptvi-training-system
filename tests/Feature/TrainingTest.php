<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingTest extends TestCase
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

    private function paidTrainee(): Applicant
    {
        $program = Program::first();
        $batch = Batch::create(['program_id' => $program->id, 'code' => 'B', 'class_session' => 'Morning', 'class_days' => 'Mon–Fri', 'capacity' => 20, 'status' => 'Ongoing']);

        return Applicant::create([
            'program_id' => $program->id, 'batch_id' => $batch->id, 'status' => 'Paid', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '09',
        ]);
    }

    public function test_module_scoped_to_coordinator(): void
    {
        $this->actingAs($this->as('coordinator'))->get('/training')->assertOk();
        $this->actingAs($this->as('cashier'))->get('/training')->assertForbidden();
    }

    public function test_start_training_promotes_paid_to_in_training(): void
    {
        $a = $this->paidTrainee();

        $this->actingAs($this->as('coordinator'))
            ->post("/training/{$a->id}/start")
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('In training', $a->fresh()->status);
    }

    public function test_start_training_only_applies_to_paid_learners(): void
    {
        $a = $this->paidTrainee();
        $a->update(['status' => 'Qualified']); // not Paid yet

        $this->actingAs($this->as('coordinator'))->post("/training/{$a->id}/start")->assertRedirect();
        $this->assertSame('Qualified', $a->fresh()->status);
    }

    public function test_cashier_cannot_start_training(): void
    {
        $a = $this->paidTrainee();
        $this->actingAs($this->as('cashier'))
            ->post("/training/{$a->id}/start")
            ->assertForbidden();
    }
}
