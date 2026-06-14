<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Document;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTest extends TestCase
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

    private function applicant(): Applicant
    {
        return Applicant::create([
            'program_id' => Program::first()->id, 'status' => 'Registered', 'active' => true,
            'last_name' => 'Cruz', 'first_name' => 'Juan', 'barangay' => 'Poblacion', 'contact' => '0917',
        ]);
    }

    public function test_saving_a_note_creates_the_document_record(): void
    {
        $a = $this->applicant();

        $this->actingAs($this->as('registrar'))
            ->post("/applicants/{$a->id}/documents", [
                'requirement_key' => 1,
                'status' => 'Submitted',
                'note' => 'Photocopy only, original to follow',
            ])
            ->assertRedirect();

        $doc = Document::where('applicant_id', $a->id)->where('requirement_key', 1)->first();
        $this->assertNotNull($doc);
        $this->assertSame('Submitted', $doc->status);
        $this->assertSame('Photocopy only, original to follow', $doc->note);
        $this->assertSame($this->as('registrar')->id, $doc->noted_by);
    }

    public function test_saving_again_updates_the_same_record(): void
    {
        $a = $this->applicant();
        $registrar = $this->as('registrar');

        $this->actingAs($registrar)->post("/applicants/{$a->id}/documents", [
            'requirement_key' => 2, 'status' => 'Pending', 'note' => '',
        ]);
        $this->actingAs($registrar)->post("/applicants/{$a->id}/documents", [
            'requirement_key' => 2, 'status' => 'Not applicable', 'note' => 'No PSA — gave barangay cert',
        ]);

        $this->assertSame(1, Document::where('applicant_id', $a->id)->where('requirement_key', 2)->count());
        $doc = Document::where('applicant_id', $a->id)->where('requirement_key', 2)->first();
        $this->assertSame('Not applicable', $doc->status);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $a = $this->applicant();
        $this->actingAs($this->as('registrar'))
            ->post("/applicants/{$a->id}/documents", ['requirement_key' => 0, 'status' => 'Verified'])
            ->assertSessionHasErrors('status');
    }

    public function test_cashier_cannot_edit_document_notes(): void
    {
        $a = $this->applicant();
        $this->actingAs($this->as('cashier'))
            ->post("/applicants/{$a->id}/documents", [
                'requirement_key' => 1, 'status' => 'Submitted', 'note' => 'x',
            ])
            ->assertForbidden();
    }

    public function test_documents_complete_counts_submitted_and_not_applicable(): void
    {
        $a = $this->applicant();
        $this->assertFalse($a->documentsComplete());

        foreach (config('requirements') as $i => $req) {
            Document::create([
                'applicant_id' => $a->id,
                'requirement_key' => $req['key'],
                // Mix of both settled statuses — both should count.
                'status' => $i % 2 === 0 ? 'Submitted' : 'Not applicable',
            ]);
        }

        $this->assertTrue($a->fresh()->documentsComplete());
    }

    public function test_pending_requirement_keeps_documents_incomplete(): void
    {
        $a = $this->applicant();
        foreach (config('requirements') as $req) {
            Document::create([
                'applicant_id' => $a->id,
                'requirement_key' => $req['key'],
                'status' => 'Pending',
            ]);
        }
        $this->assertFalse($a->fresh()->documentsComplete());
    }

    public function test_show_hides_documents_from_non_pii_role(): void
    {
        $a = $this->applicant();
        $this->actingAs($this->as('cashier'))
            ->get("/applicants/{$a->id}")
            ->assertInertia(fn ($p) => $p->where('documents', null)->where('pii', false));
    }
}
