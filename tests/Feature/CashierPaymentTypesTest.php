<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Payment;
use App\Models\Program;
use App\Models\User;
use App\Support\Money;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierPaymentTypesTest extends TestCase
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
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Pob', 'contact' => '0917',
        ]);
    }

    public function test_cashier_can_receive_a_non_fee_payment_like_uniform(): void
    {
        $a = $this->qualified();
        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 350, 'category' => 'School uniform', 'description' => '1 set, size M',
            'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ])->assertRedirect();

        $p = Payment::where('applicant_id', $a->id)->firstOrFail();
        $this->assertSame('School uniform', $p->category);
        $this->assertSame('1 set, size M', $p->description);
    }

    public function test_uniform_payment_does_not_touch_fee_balance_or_pipeline(): void
    {
        $a = $this->qualified();
        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 1000, 'category' => 'School uniform', 'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ])->assertRedirect();

        $a->refresh();
        $this->assertSame(0, $a->paidTotal());        // fee paid still 0
        $this->assertSame(1000, $a->balance());       // full fee still outstanding
        $this->assertSame('Qualified', $a->status);   // pipeline unchanged
        $this->assertSame(1000, $a->otherCollected());
    }

    public function test_training_fee_payment_still_advances_pipeline(): void
    {
        $a = $this->qualified();
        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 1000, 'category' => 'Miscellaneous fee', 'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ])->assertRedirect();

        $a->refresh();
        $this->assertSame(0, $a->balance());
        $this->assertSame('Paid', $a->status);
    }

    public function test_invalid_category_is_rejected(): void
    {
        $a = $this->qualified();
        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 100, 'category' => 'Bribe', 'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ])->assertSessionHasErrors('category');
    }

    public function test_others_category_requires_a_description(): void
    {
        $a = $this->qualified();
        // No description → rejected.
        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 100, 'category' => 'Others', 'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ])->assertSessionHasErrors('description');

        // With a description → accepted.
        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 100, 'category' => 'Others', 'description' => 'Graduation photo',
            'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ])->assertRedirect();

        $this->assertDatabaseHas('payments', ['applicant_id' => $a->id, 'category' => 'Others', 'description' => 'Graduation photo']);
    }

    public function test_legacy_payment_without_category_defaults_to_training_fee(): void
    {
        $a = $this->qualified();
        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 500, 'type' => 'Partial', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ])->assertRedirect();

        $p = Payment::where('applicant_id', $a->id)->firstOrFail();
        $this->assertSame('Miscellaneous fee', $p->category);
        $this->assertSame(500, $a->fresh()->paidTotal());
    }

    public function test_receipt_prints_for_cashier_and_shows_details(): void
    {
        $a = $this->qualified();
        $p = Payment::create([
            'applicant_id' => $a->id, 'amount' => 350, 'category' => 'School uniform',
            'description' => 'size M', 'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-10',
            'or_number' => 'OR-0777',
        ]);

        \App\Models\Setting::put('org_address', 'Poblacion, Magsaysay, Davao del Sur');

        $res = $this->actingAs($this->as('cashier'))->get("/cashier/payments/{$p->id}/receipt");
        $res->assertOk();
        $res->assertSee('ACKNOWLEDGEMENT RECEIPT');
        $res->assertSee('School uniform');
        $res->assertSee('Three Hundred Fifty Pesos');
        // Relabelled OR No. → Control No., and the OR- prefix reads CN- on the receipt.
        $res->assertSee('Control No.');
        $res->assertDontSee('OR No.');
        $res->assertSee('CN-');
        // Two quarter-page copies; the file copy carries a COPY watermark, and the
        // old "Trainee's Copy / File Copy" tags are gone.
        $res->assertSee('watermark');
        $res->assertSee('>COPY<', false);
        $res->assertDontSee('TRAINEE');
        $res->assertDontSee('FILE COPY');
        // The PESO (Magsaysay municipal) logo is removed; institute logo stays.
        $res->assertDontSee('magsaysay-logo.png');
        $res->assertSee('mptvi-logo.png');
        // Editable address from institution settings is printed.
        $res->assertSee('Poblacion, Magsaysay, Davao del Sur');
    }

    public function test_payment_type_is_limited_to_full_payment_and_partial(): void
    {
        $a = $this->qualified();
        // Retired types are no longer accepted.
        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 100, 'category' => 'Miscellaneous fee', 'type' => 'Down', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ])->assertSessionHasErrors('type');

        $this->actingAs($this->as('cashier'))->get('/cashier')
            ->assertInertia(fn ($p) => $p->where('types', ['Full Payment', 'Partial']));
    }

    public function test_cashier_index_exposes_categories_and_learners(): void
    {
        $this->qualified();
        $this->actingAs($this->as('cashier'))->get('/cashier')
            ->assertInertia(fn ($p) => $p
                ->where('trainingFeeCategory', 'Miscellaneous fee')
                ->has('categories')
                ->has('learners', 1));
    }

    public function test_or_number_is_auto_generated_sequentially(): void
    {
        $a = $this->qualified();
        $cashier = $this->as('cashier');

        $this->actingAs($cashier)->post("/cashier/{$a->id}/payments", [
            'amount' => 100, 'category' => 'School uniform', 'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ]);
        $this->actingAs($cashier)->post("/cashier/{$a->id}/payments", [
            'amount' => 200, 'category' => 'Assessment fee', 'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ]);

        $ors = Payment::orderBy('id')->pluck('or_number')->all();
        $this->assertSame(['OR-0001', 'OR-0002'], $ors);
    }

    public function test_or_number_continues_from_existing_max(): void
    {
        $a = $this->qualified();
        Payment::create(['applicant_id' => $a->id, 'amount' => 50, 'category' => 'School uniform',
            'type' => 'Full Payment', 'method' => 'Cash', 'or_number' => 'OR-0042', 'paid_at' => '2026-06-01']);

        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 100, 'category' => 'School uniform', 'type' => 'Full Payment', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ]);

        $this->assertSame('OR-0043', Payment::latest('id')->first()->or_number);
    }

    public function test_money_in_words(): void
    {
        $this->assertSame('One Thousand Pesos', Money::inWords(1000));
        $this->assertSame('Three Hundred Fifty Pesos', Money::inWords(350));
        $this->assertSame('One Thousand Two Hundred Fifty Pesos', Money::inWords(1250));
        $this->assertSame('Zero Pesos', Money::inWords(0));
    }
}
