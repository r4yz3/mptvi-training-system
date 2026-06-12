<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Document;
use App\Models\DocumentAudit;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(ProgramSeeder::class);
        Storage::fake('local');
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

    public function test_upload_stores_on_private_disk_and_audits(): void
    {
        $a = $this->applicant();

        $this->actingAs($this->as('registrar'))
            ->post("/applicants/{$a->id}/documents", [
                'requirement_key' => 1,
                'file' => UploadedFile::fake()->create('barangay.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $doc = Document::where('applicant_id', $a->id)->where('requirement_key', 1)->first();
        $this->assertNotNull($doc);
        $this->assertSame('Submitted', $doc->status);
        $this->assertCount(1, $doc->files);
        Storage::disk('local')->assertExists($doc->files->first()->path);
        $this->assertTrue($doc->files->first()->path !== null
            && str_starts_with($doc->files->first()->path, "documents/{$a->id}/"));
        $this->assertSame(1, DocumentAudit::where('action', 'upload')->count());
    }

    public function test_cashier_cannot_upload_or_download(): void
    {
        $a = $this->applicant();
        $this->actingAs($this->as('cashier'))
            ->post("/applicants/{$a->id}/documents", [
                'requirement_key' => 1,
                'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_verify_and_reject_flow(): void
    {
        $a = $this->applicant();
        $registrar = $this->as('registrar');

        $this->actingAs($registrar)->post("/applicants/{$a->id}/documents", [
            'requirement_key' => 0,
            'file' => UploadedFile::fake()->image('photo.jpg'),
        ]);
        $doc = Document::first();

        $this->actingAs($registrar)->put("/documents/{$doc->id}/verify")->assertRedirect();
        $this->assertSame('Verified', $doc->fresh()->status);

        $this->actingAs($registrar)->put("/documents/{$doc->id}/reject", ['reason' => 'Blurry'])->assertRedirect();
        $this->assertSame('Rejected', $doc->fresh()->status);
        $this->assertSame('Blurry', $doc->fresh()->reject_reason);
    }

    public function test_physical_item_toggles_received(): void
    {
        $a = $this->applicant();
        // key 4 = Brown Envelope (physical)
        $this->actingAs($this->as('registrar'))
            ->post("/applicants/{$a->id}/documents/physical", ['requirement_key' => 4])
            ->assertRedirect();

        $doc = Document::where('requirement_key', 4)->first();
        $this->assertSame('Verified', $doc->status);
    }

    public function test_download_requires_pii_and_is_audited(): void
    {
        $a = $this->applicant();
        $this->actingAs($this->as('registrar'))->post("/applicants/{$a->id}/documents", [
            'requirement_key' => 1,
            'file' => UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf'),
        ]);
        $file = \App\Models\DocumentFile::first();

        // cashier (no pii.view) blocked
        $this->actingAs($this->as('cashier'))
            ->get("/document-files/{$file->id}/download")
            ->assertForbidden();

        // registrar (pii.view) allowed + audited
        $this->actingAs($this->as('registrar'))
            ->get("/document-files/{$file->id}/download")
            ->assertOk();
        $this->assertSame(1, DocumentAudit::where('action', 'download')->count());
    }

    public function test_show_hides_documents_from_non_pii_role(): void
    {
        $a = $this->applicant();
        $this->actingAs($this->as('cashier'))
            ->get("/applicants/{$a->id}")
            ->assertInertia(fn ($p) => $p->where('documents', null)->where('pii', false));
    }
}
