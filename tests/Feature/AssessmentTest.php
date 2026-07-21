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

    public function test_admin_registrar_set_assessment_result_on_the_profile(): void
    {
        $a = $this->trainee('In training');

        $this->actingAs($this->as('registrar'))
            ->put("/applicants/{$a->id}/assessment", ['assessment_result' => 'Competent'])
            ->assertRedirect();
        $this->assertSame('Competent', $a->fresh()->assessment_result);
        // Result is independent of the pipeline status — status is unchanged.
        $this->assertSame('In training', $a->fresh()->status);

        // Re-mark and clear.
        $this->actingAs($this->as('admin'))
            ->put("/applicants/{$a->id}/assessment", ['assessment_result' => 'Not Yet Competent'])
            ->assertRedirect();
        $this->assertSame('Not Yet Competent', $a->fresh()->assessment_result);

        $this->actingAs($this->as('admin'))
            ->put("/applicants/{$a->id}/assessment", ['assessment_result' => null])
            ->assertRedirect();
        $this->assertNull($a->fresh()->assessment_result);
    }

    public function test_invalid_result_is_rejected(): void
    {
        $a = $this->trainee('In training');
        $this->actingAs($this->as('admin'))
            ->put("/applicants/{$a->id}/assessment", ['assessment_result' => 'Maybe'])
            ->assertSessionHasErrors('assessment_result');
    }

    public function test_cashier_cannot_set_assessment_or_view_the_roster(): void
    {
        $a = $this->trainee('In training');
        // cashier lacks the 'assess' cap and the assessment module.
        $this->actingAs($this->as('cashier'))
            ->put("/applicants/{$a->id}/assessment", ['assessment_result' => 'Competent'])
            ->assertForbidden();
        $this->actingAs($this->as('cashier'))->get('/assessment')->assertForbidden();
        $this->actingAs($this->as('coordinator'))->get('/assessment')->assertOk();
    }

    public function test_certificate_route_is_gone(): void
    {
        $a = $this->trainee('Certified');
        $this->actingAs($this->as('admin'))->get("/assessment/{$a->id}/certificate")->assertNotFound();
    }
}
