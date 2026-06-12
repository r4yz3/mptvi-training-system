<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(ProgramSeeder::class);
        Applicant::create(['program_id' => Program::first()->id, 'status' => 'Certified', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '09']);
    }

    private function as(string $role): User
    {
        return User::role($role)->firstOrFail();
    }

    public function test_module_scoped_to_admin_and_secretary(): void
    {
        $this->actingAs($this->as('manager'))->get('/reports')->assertOk();
        $this->actingAs($this->as('cashier'))->get('/reports')->assertForbidden();
    }

    public function test_dashboard_is_role_aware(): void
    {
        // cashier dashboard has no pipeline / no ₱ totals
        $this->actingAs($this->as('cashier'))->get('/dashboard')
            ->assertInertia(fn (Assert $p) => $p->component('Dashboard')->where('pipeline', null)->has('cards'));

        // admin dashboard has the pipeline
        $this->actingAs($this->as('admin'))->get('/dashboard')
            ->assertInertia(fn (Assert $p) => $p->has('pipeline', 6));
    }

    public function test_applicants_csv_downloads(): void
    {
        $res = $this->actingAs($this->as('admin'))->get('/reports/applicants.csv');
        $res->assertOk();
        $res->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $res->streamedContent();
        $this->assertStringContainsString('ULI', $content);
        $this->assertStringContainsString('Cruz', $content);
        $this->assertStringContainsString('Juan', $content);
    }

    public function test_payments_csv_requires_finance_view(): void
    {
        // secretary can reach reports but NOT finance (admin-only)
        $this->actingAs($this->as('manager'))->get('/reports/payments.csv')->assertForbidden();
        $this->actingAs($this->as('admin'))->get('/reports/payments.csv')->assertOk();
    }
}
