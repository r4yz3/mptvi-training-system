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
        $this->assertStringContainsString('Last name', $content);
        $this->assertStringContainsString('Cruz', $content);
        $this->assertStringContainsString('Juan', $content);
    }

    public function test_payments_csv_requires_finance_view(): void
    {
        // secretary can reach reports but NOT finance (admin-only)
        $this->actingAs($this->as('manager'))->get('/reports/payments.csv')->assertForbidden();
        $this->actingAs($this->as('admin'))->get('/reports/payments.csv')->assertOk();
    }

    public function test_complete_columns_export_includes_full_lpf_fields(): void
    {
        $res = $this->actingAs($this->as('admin'))->get('/reports/applicants.csv?columns=full');
        $res->assertOk();
        $content = $res->streamedContent();

        // Summary export would NOT carry these; the complete set does.
        $this->assertStringContainsString('Birthdate', $content);
        $this->assertStringContainsString('Mother', $content);
        $this->assertStringContainsString('Guardian', $content);
        $this->assertStringContainsString('Emergency contact', $content);
        $this->assertStringContainsString('Cruz', $content);
    }

    public function test_applicants_export_as_excel(): void
    {
        $res = $this->actingAs($this->as('admin'))->get('/reports/applicants.csv?format=xlsx&columns=full');
        $res->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $res->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', (string) $res->headers->get('content-disposition'));

        // The download is a real Office Open XML package (a ZIP → starts with "PK").
        $bytes = file_get_contents($res->baseResponse->getFile()->getPathname());
        $this->assertStringStartsWith('PK', $bytes);
    }

    public function test_payments_export_as_excel(): void
    {
        $res = $this->actingAs($this->as('admin'))->get('/reports/payments.csv?format=xlsx');
        $res->assertOk();
        $this->assertStringContainsString('.xlsx', (string) $res->headers->get('content-disposition'));
    }
}
