<?php

namespace Tests\Feature;

use App\Models\DownloadRequest;
use App\Models\User;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DownloadTest extends TestCase
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

    public function test_cashier_now_has_finance_view_but_not_approval(): void
    {
        $cashier = $this->as('cashier');
        $this->assertTrue($cashier->can('finance.view'));
        $this->assertTrue($cashier->can('download.request'));
        $this->assertFalse($cashier->can('download.approve'));
    }

    public function test_cashier_cannot_direct_download_but_admin_can(): void
    {
        $this->actingAs($this->as('cashier'))->get('/cashier/export.csv')->assertForbidden();
        $this->actingAs($this->as('admin'))->get('/cashier/export.csv')->assertOk();
    }

    public function test_cashier_request_creates_a_pending_record(): void
    {
        $cashier = $this->as('cashier');
        $this->actingAs($cashier)
            ->post('/downloads', ['type' => 'cashier_csv', 'params' => ['status' => 'valid']])
            ->assertRedirect();

        $this->assertDatabaseHas('download_requests', [
            'user_id' => $cashier->id, 'type' => 'cashier_csv', 'status' => 'pending',
        ]);
    }

    public function test_cashier_cannot_request_a_report_it_lacks_the_capability_for(): void
    {
        // applicants_csv requires the 'export' cap, which the cashier does not hold.
        $this->actingAs($this->as('cashier'))
            ->post('/downloads', ['type' => 'applicants_csv'])
            ->assertForbidden();
    }

    public function test_pending_file_is_blocked_until_an_admin_approves(): void
    {
        $cashier = $this->as('cashier');
        $this->actingAs($cashier)->post('/downloads', ['type' => 'cashier_csv']);
        $dr = DownloadRequest::where('user_id', $cashier->id)->firstOrFail();

        // Not yet approved → blocked.
        $this->actingAs($cashier)->get("/downloads/{$dr->id}/file")->assertForbidden();

        // Admin approves.
        $this->actingAs($this->as('admin'))->put("/downloads/{$dr->id}/approve")->assertRedirect();
        $this->assertSame('approved', $dr->fresh()->status);

        // Now the requester can download, and it's marked downloaded.
        $this->actingAs($cashier)->get("/downloads/{$dr->id}/file")->assertOk();
        $this->assertSame('downloaded', $dr->fresh()->status);
    }

    public function test_admin_request_is_auto_approved_and_downloadable(): void
    {
        $admin = $this->as('admin');
        $this->actingAs($admin)->post('/downloads', ['type' => 'cashier_csv']);
        $dr = DownloadRequest::where('user_id', $admin->id)->firstOrFail();

        $this->assertSame('approved', $dr->status);
        $this->actingAs($admin)->get("/downloads/{$dr->id}/file")->assertOk();
    }

    public function test_reject_records_a_reason_and_blocks_download(): void
    {
        $cashier = $this->as('cashier');
        $this->actingAs($cashier)->post('/downloads', ['type' => 'cashier_csv']);
        $dr = DownloadRequest::where('user_id', $cashier->id)->firstOrFail();

        $this->actingAs($this->as('admin'))
            ->put("/downloads/{$dr->id}/reject", ['reason' => 'Not needed this period'])
            ->assertRedirect();

        $dr->refresh();
        $this->assertSame('rejected', $dr->status);
        $this->assertSame('Not needed this period', $dr->reason);
        $this->actingAs($cashier)->get("/downloads/{$dr->id}/file")->assertForbidden();
    }

    public function test_non_admin_cannot_approve(): void
    {
        $cashier = $this->as('cashier');
        $this->actingAs($cashier)->post('/downloads', ['type' => 'cashier_csv']);
        $dr = DownloadRequest::where('user_id', $cashier->id)->firstOrFail();

        $this->actingAs($this->as('coordinator'))->put("/downloads/{$dr->id}/approve")->assertForbidden();
        $this->assertSame('pending', $dr->fresh()->status);
    }

    public function test_a_requester_cannot_download_someone_elses_request(): void
    {
        $cashier = $this->as('cashier');
        $this->actingAs($cashier)->post('/downloads', ['type' => 'cashier_csv']);
        $dr = DownloadRequest::where('user_id', $cashier->id)->firstOrFail();
        $this->actingAs($this->as('admin'))->put("/downloads/{$dr->id}/approve");

        // A different non-approver (coordinator) must not fetch the cashier's file.
        $this->actingAs($this->as('coordinator'))->get("/downloads/{$dr->id}/file")->assertForbidden();
    }

    public function test_downloads_page_loads_for_cashier(): void
    {
        $this->actingAs($this->as('cashier'))->get('/downloads')->assertOk();
    }
}
