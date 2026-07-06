<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchAssignTest extends TestCase
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

    private function makeApplicant(array $attrs = []): Applicant
    {
        return Applicant::create(array_merge([
            'program_id' => Program::first()->id, 'status' => 'Qualified', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Poblacion', 'contact' => '0917',
        ], $attrs));
    }

    private function makeBatch(array $attrs = []): Batch
    {
        return Batch::create(array_merge([
            'program_id' => Program::first()->id, 'code' => '2026-T', 'class_session' => 'Morning',
            'class_days' => 'Mon–Fri', 'capacity' => 2, 'status' => 'Open',
        ], $attrs));
    }

    public function test_coordinator_can_add_learner_to_batch(): void
    {
        $batch = $this->makeBatch();
        $a = $this->makeApplicant();

        $this->actingAs($this->as('coordinator'))
            ->post("/batches/{$batch->id}/learners", ['applicant_id' => $a->id])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame($batch->id, $a->fresh()->batch_id);
    }

    public function test_cross_program_add_requires_explicit_move(): void
    {
        [$progA, $progB] = Program::take(2)->get();
        $batch = $this->makeBatch(['program_id' => $progB->id]);
        $a = $this->makeApplicant(['program_id' => $progA->id]);

        $this->actingAs($this->as('admin'))
            ->post("/batches/{$batch->id}/learners", ['applicant_id' => $a->id])
            ->assertSessionHas('error');
        $this->assertNull($a->fresh()->batch_id);

        $this->actingAs($this->as('admin'))
            ->post("/batches/{$batch->id}/learners", ['applicant_id' => $a->id, 'move_program' => true])
            ->assertSessionHas('success');
        $a->refresh();
        $this->assertSame($batch->id, $a->batch_id);
        $this->assertSame($progB->id, $a->program_id);
    }

    public function test_full_or_finished_batch_rejects_adds(): void
    {
        $full = $this->makeBatch(['capacity' => 1]);
        $this->makeApplicant(['batch_id' => $full->id]);
        $a = $this->makeApplicant(['first_name' => 'Maria']);

        $this->actingAs($this->as('admin'))
            ->post("/batches/{$full->id}/learners", ['applicant_id' => $a->id])
            ->assertSessionHas('error');
        $this->assertNull($a->fresh()->batch_id);

        $closed = $this->makeBatch(['code' => '2026-X', 'status' => 'Closed']);
        $this->actingAs($this->as('admin'))
            ->post("/batches/{$closed->id}/learners", ['applicant_id' => $a->id])
            ->assertSessionHas('error');
        $this->assertNull($a->fresh()->batch_id);
    }

    public function test_inactive_or_disqualified_learners_cannot_be_added(): void
    {
        $batch = $this->makeBatch();
        $inactive = $this->makeApplicant(['active' => false]);
        $dq = $this->makeApplicant(['first_name' => 'Pedro', 'status' => 'Disqualified']);

        foreach ([$inactive, $dq] as $a) {
            $this->actingAs($this->as('admin'))
                ->post("/batches/{$batch->id}/learners", ['applicant_id' => $a->id])
                ->assertSessionHas('error');
            $this->assertNull($a->fresh()->batch_id);
        }
    }

    public function test_learner_can_be_removed_from_batch(): void
    {
        $batch = $this->makeBatch();
        $a = $this->makeApplicant(['batch_id' => $batch->id]);

        $this->actingAs($this->as('coordinator'))
            ->delete("/batches/{$batch->id}/learners/{$a->id}")
            ->assertSessionHas('success');

        $this->assertNull($a->fresh()->batch_id);
    }

    public function test_program_move_clears_old_batch(): void
    {
        [$progA, $progB] = Program::take(2)->get();
        $batch = $this->makeBatch(['program_id' => $progA->id]);
        $a = $this->makeApplicant(['program_id' => $progA->id, 'batch_id' => $batch->id]);

        $this->actingAs($this->as('admin'))
            ->post("/programs/{$progB->id}/learners", ['applicant_id' => $a->id])
            ->assertSessionHas('success');

        $a->refresh();
        $this->assertSame($progB->id, $a->program_id);
        $this->assertNull($a->batch_id);
    }

    public function test_cashier_cannot_touch_batch_assignment(): void
    {
        $batch = $this->makeBatch();
        $a = $this->makeApplicant();

        $this->actingAs($this->as('cashier'))
            ->post("/batches/{$batch->id}/learners", ['applicant_id' => $a->id])
            ->assertForbidden();
        $this->assertNull($a->fresh()->batch_id);
    }
}
