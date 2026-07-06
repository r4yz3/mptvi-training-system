<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdSystemTest extends TestCase
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

    private function makeLearner(): Applicant
    {
        return Applicant::create([
            'program_id' => Program::first()->id, 'status' => 'Paid', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '09',
        ]);
    }

    public function test_module_scoped_to_admin_secretary_registrar(): void
    {
        $this->actingAs($this->as('registrar'))->get('/idsystem')->assertOk();
        $this->actingAs($this->as('cashier'))->get('/idsystem')->assertForbidden();
        $this->actingAs($this->as('coordinator'))->get('/idsystem')->assertForbidden();
    }

    public function test_issue_requires_id_issue_cap_and_stamps_date(): void
    {
        $a = $this->makeLearner();

        // registrar has id.issue
        $this->actingAs($this->as('registrar'))->put("/idsystem/{$a->id}/issue")->assertRedirect();
        $this->assertNotNull($a->fresh()->id_issued_at);
    }

    public function test_card_loads_after_an_id_is_issued(): void
    {
        $a = $this->makeLearner();
        $this->actingAs($this->as('registrar'))->put("/idsystem/{$a->id}/issue");

        // Regression: id_issued_at must be a date cast, not a raw string, or the
        // card 500s on $applicant->id_issued_at?->toDateString().
        $this->actingAs($this->as('registrar'))->get("/idsystem/{$a->id}")->assertOk();
    }

    public function test_index_lists_all_learners(): void
    {
        $this->makeLearner();
        Applicant::create(['program_id' => Program::first()->id, 'status' => 'Registered', 'active' => true,
            'last_name' => 'Reyes', 'first_name' => 'X', 'barangay' => 'Z', 'contact' => '0']);

        $this->actingAs($this->as('registrar'))->get('/idsystem')
            ->assertInertia(fn ($p) => $p->has('applicants.data', 2));
    }
}
