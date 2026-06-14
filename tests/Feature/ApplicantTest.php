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

class ApplicantTest extends TestCase
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

    private function makeApplicant(array $overrides = []): Applicant
    {
        return Applicant::create(array_merge([
            'uli' => 'MPT-26-0001',
            'program_id' => Program::first()->id,
            'status' => 'Registered',
            'active' => true,
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'barangay' => 'Poblacion',
            'contact' => '0917-555-0001',
            'sex' => 'Male',
            'birthdate' => '2000-01-01',
            'age' => 26,
            'voter' => true,
        ], $overrides));
    }

    public function test_index_lists_applicants_for_any_staff(): void
    {
        $this->makeApplicant();
        $this->actingAs($this->as('cashier'))
            ->get('/applicants')
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Applicants/Index')->has('applicants.data', 1));
    }

    public function test_registrar_can_register_applicant_and_uli_is_generated(): void
    {
        $this->actingAs($this->as('registrar'))
            ->post('/applicants', [
                'last_name' => 'Reyes',
                'first_name' => 'Pedro',
                'barangay' => 'Bacungan',
                'contact' => '0918-555-0102',
                'sex' => 'Male',
                'program_id' => Program::first()->id,
                'birthdate' => '1998-05-20',
            ])
            ->assertRedirect();

        $a = Applicant::where('last_name', 'Reyes')->first();
        $this->assertNotNull($a);
        $this->assertStringStartsWith('MPT-', $a->uli);
        $this->assertSame('Registered', $a->status);
        $this->assertSame(28, $a->age); // derived from birthdate
    }

    public function test_cashier_cannot_register_applicant(): void
    {
        $this->actingAs($this->as('cashier'))
            ->post('/applicants', [
                'last_name' => 'X', 'first_name' => 'Y', 'barangay' => 'Z',
                'contact' => '0900', 'sex' => 'Male', 'program_id' => Program::first()->id,
            ])
            ->assertForbidden();
    }

    public function test_pii_role_sees_full_profile(): void
    {
        $a = $this->makeApplicant();
        $this->actingAs($this->as('registrar'))
            ->get("/applicants/{$a->id}")
            ->assertInertia(fn (Assert $p) => $p
                ->component('Applicants/Show')
                ->where('pii', true)
                ->where('applicant.contact', '0917-555-0001')
                ->where('applicant.barangay', 'Poblacion'));
    }

    public function test_non_pii_role_gets_redacted_profile(): void
    {
        $a = $this->makeApplicant();
        $this->actingAs($this->as('cashier'))
            ->get("/applicants/{$a->id}")
            ->assertInertia(fn (Assert $p) => $p
                ->component('Applicants/Show')
                ->where('pii', false)
                ->missing('applicant.contact')
                ->missing('applicant.barangay')
                ->missing('applicant.birthdate')
                ->where('applicant.display_name', 'Juan Santos Dela Cruz'));
    }

    public function test_toggle_active_requires_active_cap(): void
    {
        $a = $this->makeApplicant();

        // cashier lacks 'active'
        $this->actingAs($this->as('cashier'))
            ->put("/applicants/{$a->id}/active")
            ->assertForbidden();

        // registrar has 'active'
        $this->actingAs($this->as('registrar'))
            ->put("/applicants/{$a->id}/active")
            ->assertRedirect();
        $this->assertFalse($a->fresh()->active);
    }

    public function test_only_admin_can_delete_applicant(): void
    {
        $a = $this->makeApplicant();

        $this->actingAs($this->as('registrar'))
            ->delete("/applicants/{$a->id}")
            ->assertForbidden();

        $this->actingAs($this->as('admin'))
            ->delete("/applicants/{$a->id}")
            ->assertRedirect('/applicants');
        $this->assertDatabaseMissing('applicants', ['id' => $a->id]);
    }

    public function test_verification_dates_and_names_are_saved(): void
    {
        // Note-only: no signature files — just the typed names + dates on the LPF.
        $this->actingAs($this->as('registrar'))->post('/applicants', [
            'last_name' => 'Signer', 'first_name' => 'Sam', 'barangay' => 'Pob',
            'contact' => '0917', 'sex' => 'Male', 'program_id' => Program::first()->id,
            'date_accomplished' => '2026-06-12', 'date_received' => '2026-06-12',
            'interviewed_by' => 'Juan Interviewer',
        ])->assertRedirect();

        $a = Applicant::where('last_name', 'Signer')->first();
        $this->assertSame('Juan Interviewer', $a->interviewed_by);
        $this->assertSame('2026-06-12', $a->date_accomplished->toDateString());
    }

    public function test_filtered_csv_export_respects_filters_and_export_cap(): void
    {
        $this->makeApplicant(['status' => 'Certified', 'uli' => 'MPT-26-0100']);
        $this->makeApplicant(['status' => 'Registered', 'first_name' => 'Ana', 'last_name' => 'Lim', 'uli' => null]);

        // Direct download is admin-only now; other staff route through the Downloads queue.
        $res = $this->actingAs($this->as('admin'))->get('/applicants/export.csv?status=Certified');
        $res->assertOk();
        $res->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $res->streamedContent();
        $this->assertStringContainsString('Dela Cruz', $content); // the Certified one
        $this->assertStringNotContainsString('Lim', $content);    // filtered out

        // Non-admins can't hit the direct route — they must request via the queue.
        $this->actingAs($this->as('registrar'))->get('/applicants/export.csv')->assertForbidden();
        $this->actingAs($this->as('cashier'))->get('/applicants/export.csv')->assertForbidden();
    }

    public function test_pdf_report_renders_for_admin_and_is_locked_for_others(): void
    {
        $this->makeApplicant();
        $res = $this->actingAs($this->as('admin'))->get('/applicants/report');
        $res->assertOk();
        $res->assertSee('APPLICANTS / LEARNERS REPORT', false);

        // Direct access is admin-only; registrar requests through the Downloads queue.
        $this->actingAs($this->as('registrar'))->get('/applicants/report')->assertForbidden();
    }

    public function test_print_form_is_pii_gated(): void
    {
        $a = $this->makeApplicant();

        // pii role gets the printable LPF
        $res = $this->actingAs($this->as('registrar'))->get("/applicants/{$a->id}/print");
        $res->assertOk();
        $res->assertSee("LEARNER'S PROFILE", false);
        $res->assertSee($a->display_name);

        // non-pii role blocked
        $this->actingAs($this->as('cashier'))->get("/applicants/{$a->id}/print")->assertForbidden();
    }

    public function test_index_filters_by_status(): void
    {
        $this->makeApplicant(['status' => 'Certified', 'uli' => 'MPT-26-0002']);
        $this->makeApplicant(['status' => 'Registered', 'first_name' => 'Ana', 'last_name' => 'Lim', 'uli' => null]);

        $this->actingAs($this->as('admin'))
            ->get('/applicants?status=Certified')
            ->assertInertia(fn (Assert $p) => $p->has('applicants.data', 1)
                ->where('applicants.data.0.status', 'Certified'));
    }
}
