<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentTest extends TestCase
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

    private function trainee(string $status = 'For assessment'): Applicant
    {
        return Applicant::create([
            'program_id' => Program::first()->id, 'status' => $status, 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '09',
        ]);
    }

    public function test_competent_result_certifies_and_issues_cert_number(): void
    {
        $a = $this->trainee();
        $this->actingAs($this->as('coordinator'))
            ->post("/assessment/{$a->id}/result", ['result' => 'Competent', 'assessed_at' => '2026-08-01'])
            ->assertRedirect();

        $a->refresh();
        $this->assertSame('Certified', $a->status);
        $this->assertStringStartsWith('CK2-', $a->cert_number);
        $this->assertNotNull($a->certified_at);
    }

    public function test_not_yet_competent_stays_for_assessment(): void
    {
        $a = $this->trainee();
        $this->actingAs($this->as('coordinator'))
            ->post("/assessment/{$a->id}/result", ['result' => 'Not Yet Competent', 'assessed_at' => '2026-08-01'])
            ->assertRedirect();
        $this->assertSame('For assessment', $a->fresh()->status);
        $this->assertNull($a->fresh()->cert_number);
    }

    public function test_endorse_moves_in_training_to_for_assessment(): void
    {
        $a = $this->trainee('In training');
        $this->actingAs($this->as('coordinator'))
            ->put("/assessment/{$a->id}/for-assessment")
            ->assertRedirect();
        $this->assertSame('For assessment', $a->fresh()->status);
    }

    public function test_cashier_cannot_assess(): void
    {
        $a = $this->trainee();
        $this->actingAs($this->as('cashier'))->get('/assessment')->assertForbidden();
        $this->actingAs($this->as('coordinator'))->get('/assessment')->assertOk();
    }
}
