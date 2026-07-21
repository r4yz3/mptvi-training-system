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

    public function test_cashier_has_no_finance_view_but_can_request_downloads(): void
    {
        $cashier = $this->as('cashier');
        $this->assertFalse($cashier->can('finance.view'));   // finance privacy — admin only
        $this->assertTrue($cashier->can('download.request'));
        $this->assertFalse($cashier->can('download.approve'));
    }

    public function test_cashier_cannot_direct_download_but_admin_can(): void
    {
        $this->actingAs($this->as('cashier'))->get('/cashier/export.csv')->assertForbidden();
        $this->actingAs($this->as('admin'))->get('/cashier/export.csv')->assertOk();
    }

    public function test_cashier_cannot_request_the_finance_csv(): void
    {
        // Finance privacy: the cashier no longer holds finance.view, so it cannot
        // request the cashier/payments collections export.
        $this->actingAs($this->as('cashier'))
            ->post('/downloads', ['type' => 'cashier_csv'])
            ->assertForbidden();
    }

    public function test_requester_creates_a_pending_record(): void
    {
        // The manager holds download.request + pii.view (but not download.approve),
        // so an applicants export goes to the admin approval queue.
        $manager = $this->as('manager');
        $this->actingAs($manager)
            ->post('/downloads', ['type' => 'reports_applicants_csv', 'params' => ['status' => 'Registered']])
            ->assertRedirect();

        $this->assertDatabaseHas('download_requests', [
            'user_id' => $manager->id, 'type' => 'reports_applicants_csv', 'status' => 'pending',
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
        $manager = $this->as('manager');
        $this->actingAs($manager)->post('/downloads', ['type' => 'reports_applicants_csv']);
        $dr = DownloadRequest::where('user_id', $manager->id)->firstOrFail();

        // Not yet approved → blocked.
        $this->actingAs($manager)->get("/downloads/{$dr->id}/file")->assertForbidden();

        // Admin approves.
        $this->actingAs($this->as('admin'))->put("/downloads/{$dr->id}/approve")->assertRedirect();
        $this->assertSame('approved', $dr->fresh()->status);

        // Now the requester can download, and it's marked downloaded.
        $this->actingAs($manager)->get("/downloads/{$dr->id}/file")->assertOk();
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
        $manager = $this->as('manager');
        $this->actingAs($manager)->post('/downloads', ['type' => 'reports_applicants_csv']);
        $dr = DownloadRequest::where('user_id', $manager->id)->firstOrFail();

        $this->actingAs($this->as('admin'))
            ->put("/downloads/{$dr->id}/reject", ['reason' => 'Not needed this period'])
            ->assertRedirect();

        $dr->refresh();
        $this->assertSame('rejected', $dr->status);
        $this->assertSame('Not needed this period', $dr->reason);
        $this->actingAs($manager)->get("/downloads/{$dr->id}/file")->assertForbidden();
    }

    public function test_non_admin_cannot_approve(): void
    {
        $manager = $this->as('manager');
        $this->actingAs($manager)->post('/downloads', ['type' => 'reports_applicants_csv']);
        $dr = DownloadRequest::where('user_id', $manager->id)->firstOrFail();

        $this->actingAs($this->as('coordinator'))->put("/downloads/{$dr->id}/approve")->assertForbidden();
        $this->assertSame('pending', $dr->fresh()->status);
    }

    public function test_a_requester_cannot_download_someone_elses_request(): void
    {
        $manager = $this->as('manager');
        $this->actingAs($manager)->post('/downloads', ['type' => 'reports_applicants_csv']);
        $dr = DownloadRequest::where('user_id', $manager->id)->firstOrFail();
        $this->actingAs($this->as('admin'))->put("/downloads/{$dr->id}/approve");

        // A different non-approver (coordinator) must not fetch the manager's file.
        $this->actingAs($this->as('coordinator'))->get("/downloads/{$dr->id}/file")->assertForbidden();
    }

    public function test_downloads_page_loads_for_cashier(): void
    {
        $this->actingAs($this->as('cashier'))->get('/downloads')->assertOk();
    }
}
