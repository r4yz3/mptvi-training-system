<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ScreeningTest extends TestCase
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

    private function registered(array $o = []): Applicant
    {
        return Applicant::create(array_merge([
            'program_id' => Program::first()->id,
            'status' => 'Registered',
            'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan',
            'barangay' => 'Poblacion', 'contact' => '0917',
            'sex' => 'Male', 'age' => 25, 'education' => 'College Graduate',
            'privacy_consent' => true,
        ], $o));
    }

    public function test_pending_queue_shows_only_registered(): void
    {
        $this->registered();
        $this->registered(['first_name' => 'Ana', 'status' => 'Qualified']);

        $this->actingAs($this->as('registrar'))
            ->get('/screening')
            ->assertInertia(fn (Assert $p) => $p->component('Screening/Index')
                ->where('tab', 'pending')
                ->has('applicants.data', 1)
                ->where('counts.pending', 1)
                ->where('counts.qualified', 1));
    }

    public function test_registrar_can_qualify(): void
    {
        $a = $this->registered();
        $this->actingAs($this->as('registrar'))
            ->put("/screening/{$a->id}/qualify")
            ->assertRedirect();
        $this->assertSame('Qualified', $a->fresh()->status);
        $this->assertNotNull($a->fresh()->screened_at);
    }

    private function batch(int $programId, array $o = []): Batch
    {
        return Batch::create(array_merge([
            'program_id' => $programId, 'code' => '2026-A', 'class_session' => 'Morning',
            'class_days' => 'Mon–Fri', 'capacity' => 25, 'status' => 'Open',
        ], $o));
    }

    public function test_qualify_assigns_the_chosen_batch(): void
    {
        $a = $this->registered();
        $batch = $this->batch($a->program_id);

        $this->actingAs($this->as('registrar'))
            ->put("/screening/{$a->id}/qualify", ['batch_id' => $batch->id])
            ->assertRedirect();

        $a->refresh();
        $this->assertSame('Qualified', $a->status);
        $this->assertSame($batch->id, $a->batch_id);
    }

    public function test_qualify_rejects_a_batch_from_another_program(): void
    {
        $a = $this->registered(); // program #1
        $otherProgram = Program::orderBy('id')->skip(1)->first();
        $batch = $this->batch($otherProgram->id);

        $this->actingAs($this->as('registrar'))
            ->put("/screening/{$a->id}/qualify", ['batch_id' => $batch->id])
            ->assertStatus(422);

        $this->assertNull($a->fresh()->batch_id);
    }

    public function test_disqualify_requires_reason_and_stores_it(): void
    {
        $a = $this->registered();

        $this->actingAs($this->as('registrar'))
            ->put("/screening/{$a->id}/disqualify", [])
            ->assertSessionHasErrors('reason');

        $this->actingAs($this->as('registrar'))
            ->put("/screening/{$a->id}/disqualify", ['reason' => 'Underage'])
            ->assertRedirect();

        $a->refresh();
        $this->assertSame('Disqualified', $a->status);
        $this->assertSame('Underage', $a->disqualify_reason);
    }

    public function test_cashier_cannot_reach_screening_module(): void
    {
        $this->actingAs($this->as('cashier'))->get('/screening')->assertForbidden();
    }

    public function test_eligibility_flags_underage(): void
    {
        // Isolate the age check (overall eligibility also requires verified documents, tested in P4).
        $ageItem = fn (Applicant $a) => collect($a->eligibility())
            ->firstWhere('label', 'Age within training range (15–60)')['ok'];

        $young = $this->registered(['age' => 12]);
        $this->assertFalse($ageItem($young));
        $this->assertFalse($young->isEligible());

        $ok = $this->registered(['first_name' => 'Ok', 'age' => 30]);
        $this->assertTrue($ageItem($ok));
    }
}
