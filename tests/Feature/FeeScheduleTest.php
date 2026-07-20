<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\FeeItem;
use App\Models\Payment;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeScheduleTest extends TestCase
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

    // A school year with no seeded fee amounts, so tests control the schedule fully.
    private function trainee(string $sy = '2030'): Applicant
    {
        return Applicant::create([
            'program_id' => Program::first()->id,
            'school_year' => $sy,
            'status' => 'Paid', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '0917',
        ]);
    }

    public function test_fees_page_is_settings_gated(): void
    {
        $this->actingAs($this->as('admin'))->get('/settings/fees')->assertOk();
        $this->actingAs($this->as('cashier'))->get('/settings/fees')->assertForbidden();
    }

    public function test_admin_can_save_extra_fee_amounts_per_program_and_year(): void
    {
        $program = Program::first();

        $this->actingAs($this->as('admin'))->put('/settings/fees', [
            'school_year' => '2026',
            'amounts' => [
                $program->id => ['School uniform' => 500, 'Assessment fee' => 1000],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('fee_items', [
            'program_id' => $program->id, 'school_year' => '2026', 'category' => 'School uniform', 'amount' => 500,
        ]);
        $this->assertDatabaseHas('fee_items', [
            'program_id' => $program->id, 'school_year' => '2026', 'category' => 'Assessment fee', 'amount' => 1000,
        ]);
    }

    public function test_scheduled_fees_track_expected_paid_and_balance_per_trainee(): void
    {
        $a = $this->trainee();
        FeeItem::create(['program_id' => $a->program_id, 'school_year' => $a->school_year, 'category' => 'School uniform', 'amount' => 500]);
        FeeItem::create(['program_id' => $a->program_id, 'school_year' => $a->school_year, 'category' => 'Assessment fee', 'amount' => 1000]);

        // Pay part of the uniform; nothing toward assessment.
        Payment::create(['applicant_id' => $a->id, 'amount' => 200, 'category' => 'School uniform',
            'type' => 'Partial', 'method' => 'Cash', 'paid_at' => '2026-06-01']);

        $fees = collect($a->fresh()->scheduledFees())->keyBy('category');

        $this->assertSame(500, $fees['School uniform']['expected']);
        $this->assertSame(200, $fees['School uniform']['paid']);
        $this->assertSame(300, $fees['School uniform']['balance']);
        $this->assertSame('Partial', $fees['School uniform']['status']);

        $this->assertSame(1000, $fees['Assessment fee']['balance']);
        $this->assertSame('Unpaid', $fees['Assessment fee']['status']);

        $this->assertSame(1300, $a->fresh()->scheduledFeesBalance());
    }

    public function test_voided_extra_payment_does_not_count_as_paid(): void
    {
        $a = $this->trainee();
        FeeItem::create(['program_id' => $a->program_id, 'school_year' => $a->school_year, 'category' => 'School uniform', 'amount' => 500]);

        $p = Payment::create(['applicant_id' => $a->id, 'amount' => 500, 'category' => 'School uniform',
            'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-01', 'voided_at' => now()]);

        $fees = collect($a->fresh()->scheduledFees())->keyBy('category');
        $this->assertSame(0, $fees['School uniform']['paid']);
        $this->assertSame(500, $fees['School uniform']['balance']);
    }

    public function test_cashier_worklist_flags_a_trainee_who_only_owes_an_extra_fee(): void
    {
        // Fully-paid misc fee, but owes the uniform.
        $a = $this->trainee();
        Payment::create(['applicant_id' => $a->id, 'amount' => 1000, 'category' => 'Miscellaneous fee',
            'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-01']);
        FeeItem::create(['program_id' => $a->program_id, 'school_year' => $a->school_year, 'category' => 'School uniform', 'amount' => 500]);

        $this->assertSame(0, $a->fresh()->balance());          // misc settled
        $this->assertSame(500, $a->fresh()->scheduledFeesBalance());

        $this->actingAs($this->as('cashier'))->get('/cashier')
            ->assertInertia(fn ($page) => $page->has('worklist', 1)
                ->where('worklist.0.extras_balance', 500)
                ->where('worklist.0.extras.0.category', 'School uniform'));
    }
}
