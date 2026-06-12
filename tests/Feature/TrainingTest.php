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

    public function test_marking_attendance_promotes_paid_to_in_training(): void
    {
        $a = $this->paidTrainee();

        $this->actingAs($this->as('coordinator'))
            ->post("/training/{$a->id}/attendance", ['date' => '2026-06-16', 'status' => 'Present'])
            ->assertRedirect();

        $this->assertSame('In training', $a->fresh()->status);
        $this->assertSame(100, $a->fresh()->attendanceRate());
    }

    public function test_attendance_rate_counts_present_and_late(): void
    {
        $a = $this->paidTrainee();
        $coord = $this->as('coordinator');
        $this->actingAs($coord)->post("/training/{$a->id}/attendance", ['date' => '2026-06-16', 'status' => 'Present']);
        $this->actingAs($coord)->post("/training/{$a->id}/attendance", ['date' => '2026-06-17', 'status' => 'Absent']);
        $this->actingAs($coord)->post("/training/{$a->id}/attendance", ['date' => '2026-06-18', 'status' => 'Late']);

        $this->assertSame(67, $a->fresh()->attendanceRate()); // 2 of 3
    }

    public function test_registrar_cannot_mark_attendance(): void
    {
        $a = $this->paidTrainee();
        $this->actingAs($this->as('registrar'))
            ->post("/training/{$a->id}/attendance", ['date' => '2026-06-16', 'status' => 'Present'])
            ->assertForbidden();
    }
}
