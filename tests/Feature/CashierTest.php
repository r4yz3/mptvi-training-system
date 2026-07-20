<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Payment;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CashierTest extends TestCase
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

    private function qualified(): Applicant
    {
        return Applicant::create([
            'program_id' => Program::first()->id, // fee 1000
            'status' => 'Qualified', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Poblacion', 'contact' => '0917',
        ]);
    }

    public function test_module_scoped_to_admin_and_cashier(): void
    {
        $this->actingAs($this->as('cashier'))->get('/cashier')->assertOk();
        $this->actingAs($this->as('admin'))->get('/cashier')->assertOk();
        $this->actingAs($this->as('manager'))->get('/cashier')->assertForbidden();
        $this->actingAs($this->as('coordinator'))->get('/cashier')->assertForbidden();
    }

    public function test_partial_payment_keeps_qualified_full_payment_advances_to_paid(): void
    {
        $a = $this->qualified();
        $cashier = $this->as('cashier');

        $this->actingAs($cashier)->post("/cashier/{$a->id}/payments", [
            'amount' => 400, 'type' => 'Partial', 'method' => 'Cash', 'paid_at' => '2026-06-01',
        ])->assertRedirect();
        $this->assertSame('Qualified', $a->fresh()->status);
        $this->assertSame(600, $a->fresh()->balance());

        $this->actingAs($cashier)->post("/cashier/{$a->id}/payments", [
            'amount' => 600, 'type' => 'Full Payment', 'method' => 'GCash', 'paid_at' => '2026-06-05',
        ])->assertRedirect();
        $this->assertSame('Paid', $a->fresh()->status);
        $this->assertSame(0, $a->fresh()->balance());
    }

    public function test_full_payment_auto_enrolls_a_registered_trainee_without_screening(): void
    {
        // A brand-new Registered trainee — never screened/qualified.
        $a = Applicant::create([
            'program_id' => Program::first()->id, // fee 1000
            'status' => 'Registered', 'active' => true,
            'last_name' => 'Reyes', 'first_name' => 'Ana', 'barangay' => 'Pob', 'contact' => '0918',
        ]);

        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 1000, 'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-01',
        ])->assertRedirect();

        // Paying the full fee enrolls them (→ Paid) with no screening step.
        $this->assertSame('Paid', $a->fresh()->status);
    }

    public function test_disqualified_trainee_is_not_auto_enrolled_by_paying(): void
    {
        $a = Applicant::create([
            'program_id' => Program::first()->id,
            'status' => 'Disqualified', 'active' => true,
            'last_name' => 'Tan', 'first_name' => 'Bea', 'barangay' => 'Pob', 'contact' => '0919',
        ]);

        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 1000, 'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-01',
        ])->assertRedirect();

        $this->assertSame('Disqualified', $a->fresh()->status);
    }

    public function test_voiding_completing_payment_reverts_to_qualified(): void
    {
        $a = $this->qualified();
        $cashier = $this->as('cashier');

        $this->actingAs($cashier)->post("/cashier/{$a->id}/payments", [
            'amount' => 1000, 'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-01',
        ]);
        $this->assertSame('Paid', $a->fresh()->status);

        $payment = Payment::first();
        $this->actingAs($cashier)->put("/cashier/payments/{$payment->id}/void", ['reason' => 'Bounced cheque'])
            ->assertRedirect();

        $this->assertNotNull($payment->fresh()->voided_at);
        $this->assertSame('Qualified', $a->fresh()->status);
    }

    public function test_voided_payment_keeps_its_control_number_and_it_is_never_reused(): void
    {
        $a = $this->qualified();
        $cashier = $this->as('cashier');

        // First collection → CN/OR-0001.
        $this->actingAs($cashier)->post("/cashier/{$a->id}/payments", [
            'amount' => 500, 'type' => 'Partial', 'method' => 'Cash', 'paid_at' => '2026-06-01',
        ]);
        $first = Payment::latest('id')->first();
        $this->assertSame('OR-0001', $first->or_number);

        // Void it — the control number stays assigned to the voided entry.
        $this->actingAs($cashier)->put("/cashier/payments/{$first->id}/void", ['reason' => 'Wrong amount']);
        $this->assertSame('OR-0001', $first->fresh()->or_number);
        $this->assertNotNull($first->fresh()->voided_at);

        // The next collection takes the NEXT number, never reusing the voided one.
        $this->actingAs($cashier)->post("/cashier/{$a->id}/payments", [
            'amount' => 500, 'type' => 'Partial', 'method' => 'Cash', 'paid_at' => '2026-06-02',
        ]);
        $this->assertSame('OR-0002', Payment::latest('id')->first()->or_number);
    }

    public function test_coordinator_cannot_record_payment(): void
    {
        $a = $this->qualified();
        $this->actingAs($this->as('coordinator'))
            ->post("/cashier/{$a->id}/payments", ['amount' => 100, 'type' => 'Partial', 'method' => 'Cash', 'paid_at' => '2026-06-01'])
            ->assertForbidden();
    }

    public function test_payments_report_and_csv_are_finance_gated(): void
    {
        $a = $this->qualified();
        $cashier = $this->as('cashier');
        $this->actingAs($cashier)->post("/cashier/{$a->id}/payments", [
            'amount' => 500, 'type' => 'Partial', 'method' => 'Cash', 'paid_at' => '2026-06-01',
        ]);

        // cashier (no finance.view) blocked
        $this->actingAs($cashier)->get('/cashier/export.csv')->assertForbidden();
        $this->actingAs($cashier)->get('/cashier/report')->assertForbidden();

        // admin: CSV + PDF
        $res = $this->actingAs($this->as('admin'))->get('/cashier/export.csv?method=Cash');
        $res->assertOk();
        $this->assertStringContainsString('Date,"OR No.",Learner', $res->streamedContent());
        $this->assertStringContainsString('Cruz', $res->streamedContent());

        $this->actingAs($this->as('admin'))->get('/cashier/report?status=valid')
            ->assertOk()->assertSee('PAYMENTS / COLLECTIONS REPORT', false);
    }

    public function test_statement_of_account_renders_for_a_learner(): void
    {
        $a = $this->qualified();
        $cashier = $this->as('cashier');
        $this->actingAs($cashier)->post("/cashier/{$a->id}/payments", [
            'amount' => 400, 'type' => 'Partial', 'method' => 'Cash', 'paid_at' => '2026-06-01',
        ]);

        $res = $this->actingAs($cashier)->get("/cashier/{$a->id}/statement");
        $res->assertOk()
            ->assertSee('STATEMENT OF ACCOUNT', false)
            ->assertSee('Juan Cruz')
            ->assertSee('OR-0001');

        // coordinator has no cashier module
        $this->actingAs($this->as('coordinator'))->get("/cashier/{$a->id}/statement")->assertForbidden();
    }

    public function test_daily_cash_report_renders_and_is_module_gated(): void
    {
        $a = $this->qualified();
        $cashier = $this->as('cashier');
        $this->actingAs($cashier)->post("/cashier/{$a->id}/payments", [
            'amount' => 500, 'type' => 'Partial', 'method' => 'Cash', 'paid_at' => now()->toDateString(),
        ]);

        $this->actingAs($cashier)->get('/cashier/daily')
            ->assertOk()->assertSee('DAILY CASH COLLECTION REPORT', false);

        $this->actingAs($this->as('coordinator'))->get('/cashier/daily')->assertForbidden();
    }

    public function test_cashier_has_the_full_finance_view(): void
    {
        $a = $this->qualified();
        $cashier = $this->as('cashier');
        // a payment by the cashier
        $this->actingAs($cashier)->post("/cashier/{$a->id}/payments", [
            'amount' => 200, 'type' => 'Partial', 'method' => 'Cash', 'paid_at' => '2026-06-01',
        ]);
        // a payment attributed to admin directly
        Payment::create(['applicant_id' => $a->id, 'amount' => 100, 'type' => 'Partial', 'method' => 'Cash',
            'paid_at' => '2026-06-02', 'cashier_id' => $this->as('admin')->id]);

        // Cashier now has finance.view: sees aggregates + the full ledger, like admin.
        $this->actingAs($cashier)->get('/cashier')
            ->assertInertia(fn (Assert $p) => $p->where('canFinance', true)->has('ledger', 2)->has('aggregates'));

        $this->actingAs($this->as('admin'))->get('/cashier')
            ->assertInertia(fn (Assert $p) => $p->where('canFinance', true)->has('ledger', 2)->has('aggregates'));
    }
}
