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
            'amount' => 350, 'category' => 'Uniform', 'description' => '1 set, size M',
            'type' => 'Full', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ])->assertRedirect();

        $p = Payment::where('applicant_id', $a->id)->firstOrFail();
        $this->assertSame('Uniform', $p->category);
        $this->assertSame('1 set, size M', $p->description);
    }

    public function test_uniform_payment_does_not_touch_fee_balance_or_pipeline(): void
    {
        $a = $this->qualified();
        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 1000, 'category' => 'Uniform', 'type' => 'Full', 'method' => 'Cash', 'paid_at' => '2026-06-10',
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
            'amount' => 1000, 'category' => 'Training fee', 'type' => 'Full', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ])->assertRedirect();

        $a->refresh();
        $this->assertSame(0, $a->balance());
        $this->assertSame('Paid', $a->status);
    }

    public function test_invalid_category_is_rejected(): void
    {
        $a = $this->qualified();
        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 100, 'category' => 'Bribe', 'type' => 'Full', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ])->assertSessionHasErrors('category');
    }

    public function test_legacy_payment_without_category_defaults_to_training_fee(): void
    {
        $a = $this->qualified();
        $this->actingAs($this->as('cashier'))->post("/cashier/{$a->id}/payments", [
            'amount' => 500, 'type' => 'Partial', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ])->assertRedirect();

        $p = Payment::where('applicant_id', $a->id)->firstOrFail();
        $this->assertSame('Training fee', $p->category);
        $this->assertSame(500, $a->fresh()->paidTotal());
    }

    public function test_receipt_prints_for_cashier_and_shows_details(): void
    {
        $a = $this->qualified();
        $p = Payment::create([
            'applicant_id' => $a->id, 'amount' => 350, 'category' => 'Uniform',
            'description' => 'size M', 'type' => 'Full', 'method' => 'Cash', 'paid_at' => '2026-06-10',
        ]);

        $res = $this->actingAs($this->as('cashier'))->get("/cashier/payments/{$p->id}/receipt");
        $res->assertOk();
        $res->assertSee('ACKNOWLEDGEMENT RECEIPT');
        $res->assertSee('Uniform');
        $res->assertSee('Three Hundred Fifty Pesos');
    }

    public function test_cashier_index_exposes_categories_and_learners(): void
    {
        $this->qualified();
        $this->actingAs($this->as('cashier'))->get('/cashier')
            ->assertInertia(fn ($p) => $p
                ->where('trainingFeeCategory', 'Training fee')
                ->has('categories')
                ->has('learners', 1));
    }

    public function test_money_in_words(): void
    {
        $this->assertSame('One Thousand Pesos', Money::inWords(1000));
        $this->assertSame('Three Hundred Fifty Pesos', Money::inWords(350));
        $this->assertSame('One Thousand Two Hundred Fifty Pesos', Money::inWords(1250));
        $this->assertSame('Zero Pesos', Money::inWords(0));
    }
}
