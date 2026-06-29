<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraineeStatusTest extends TestCase
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

    private function trainee(): Applicant
    {
        return Applicant::create([
            'program_id' => Program::first()->id, 'status' => 'In training', 'active' => true,
            'last_name' => 'Dela Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '09',
        ]);
    }

    public function test_registrar_can_set_trainee_status(): void
    {
        $a = $this->trainee();
        $this->actingAs($this->as('registrar'))
            ->put("/applicants/{$a->id}/trainee-status", ['trainee_status' => 'Active'])
            ->assertRedirect();
        $this->assertSame('Active', $a->fresh()->trainee_status);
    }

    public function test_coordinator_can_set_trainee_status(): void
    {
        $a = $this->trainee();
        $this->actingAs($this->as('coordinator'))
            ->put("/applicants/{$a->id}/trainee-status", ['trainee_status' => 'Completed'])
            ->assertRedirect();
        $this->assertSame('Completed', $a->fresh()->trainee_status);
    }

    public function test_status_can_progress_then_complete(): void
    {
        $a = $this->trainee();
        $reg = $this->as('registrar');

        foreach (['Active', 'Inactive', 'Incomplete', 'Completed'] as $s) {
            $this->actingAs($reg)->put("/applicants/{$a->id}/trainee-status", ['trainee_status' => $s])->assertRedirect();
            $this->assertSame($s, $a->fresh()->trainee_status);
        }
    }

    public function test_status_can_be_cleared(): void
    {
        $a = $this->trainee();
        $a->update(['trainee_status' => 'Active']);

        $this->actingAs($this->as('registrar'))
            ->put("/applicants/{$a->id}/trainee-status", ['trainee_status' => ''])
            ->assertRedirect();
        $this->assertNull($a->fresh()->trainee_status);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $a = $this->trainee();
        $this->actingAs($this->as('registrar'))
            ->put("/applicants/{$a->id}/trainee-status", ['trainee_status' => 'Graduated'])
            ->assertSessionHasErrors('trainee_status');
        $this->assertNull($a->fresh()->trainee_status);
    }

    public function test_cashier_cannot_set_trainee_status(): void
    {
        $a = $this->trainee();
        $this->actingAs($this->as('cashier'))
            ->put("/applicants/{$a->id}/trainee-status", ['trainee_status' => 'Active'])
            ->assertForbidden();
        $this->assertNull($a->fresh()->trainee_status);
    }

    public function test_setting_status_does_not_change_pipeline_or_active(): void
    {
        $a = $this->trainee();
        $this->actingAs($this->as('registrar'))
            ->put("/applicants/{$a->id}/trainee-status", ['trainee_status' => 'Completed']);

        $fresh = $a->fresh();
        $this->assertSame('In training', $fresh->status); // pipeline untouched
        $this->assertTrue($fresh->active);                 // app-wide flag untouched
    }

    public function test_profile_payload_includes_trainee_status_for_full_and_limited(): void
    {
        $a = $this->trainee();
        $a->update(['trainee_status' => 'Active']);

        // registrar = pii.view (full payload)
        $this->actingAs($this->as('registrar'))->get("/applicants/{$a->id}")
            ->assertInertia(fn ($p) => $p->where('applicant.trainee_status', 'Active')->where('pii', true));

        // coordinator = no pii.view (limited payload) still sees trainee_status
        $this->actingAs($this->as('coordinator'))->get("/applicants/{$a->id}")
            ->assertInertia(fn ($p) => $p->where('applicant.trainee_status', 'Active')->where('pii', false));
    }
}
