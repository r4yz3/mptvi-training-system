<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        return User::role($role)->firstOrFail();
    }

    public function test_seeder_creates_five_roles_and_accounts(): void
    {
        $this->assertSame(5, \Spatie\Permission\Models\Role::count());
        $this->assertSame(5, User::count());
    }

    public function test_capability_matrix_matches_demo(): void
    {
        $admin = $this->userWithRole('admin');
        $manager = $this->userWithRole('manager');
        $registrar = $this->userWithRole('registrar');
        $cashier = $this->userWithRole('cashier');
        $coordinator = $this->userWithRole('coordinator');

        // admin = '*'
        $this->assertTrue($admin->can('finance.view'));
        $this->assertTrue($admin->can('payment.void'));

        // separation of duties among the base roles: only cashier records payments
        $this->assertTrue($cashier->can('payment.record'));
        $this->assertFalse($manager->can('payment.record'));
        $this->assertFalse($coordinator->can('payment.record'));

        // registrar is granted FULL administrator access — it holds every capability
        $this->assertTrue($registrar->can('payment.record'));
        $this->assertTrue($registrar->can('finance.view'));
        $this->assertTrue($registrar->can('settings'));
        $this->assertTrue($registrar->can('applicant.delete'));
        $this->assertTrue($registrar->can('download.approve'));

        // pii.view = admin + manager + registrar (NOT cashier/coordinator)
        $this->assertTrue($manager->can('pii.view'));
        $this->assertTrue($registrar->can('pii.view'));
        $this->assertFalse($cashier->can('pii.view'));
        $this->assertFalse($coordinator->can('pii.view'));

        // finance.view = admin + registrar only (NOT cashier — finance privacy — NOT manager)
        $this->assertTrue($registrar->can('finance.view'));
        $this->assertFalse($cashier->can('finance.view'));
        $this->assertFalse($manager->can('finance.view'));

        // coordinator = attendance/assess/program.manage
        $this->assertTrue($coordinator->can('program.manage'));
        $this->assertFalse($coordinator->can('screen'));
    }

    public function test_admin_can_reach_users_module(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get('/users')
            ->assertOk();
    }

    public function test_registrar_has_full_admin_access(): void
    {
        // Registrar is granted full administrator access — every admin surface opens.
        $registrar = $this->userWithRole('registrar');
        $this->actingAs($registrar)->get('/users')->assertOk();
        $this->actingAs($registrar)->get('/settings')->assertOk();
        $this->actingAs($registrar)->get('/cashier')->assertOk();
        $this->actingAs($registrar)->get('/reports')->assertOk();
        $this->actingAs($registrar)->get('/activity')->assertOk();
    }

    public function test_non_admin_blocked_from_users_module(): void
    {
        // Registrar is excluded here — it now has full admin access (see above).
        foreach (['manager', 'cashier', 'coordinator'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get('/users')
                ->assertForbidden();
        }
    }

    public function test_cashier_module_access_is_role_scoped(): void
    {
        // cashier + admin (+ full-admin registrar) may enter; others 403
        $this->actingAs($this->userWithRole('cashier'))->get('/cashier')->assertOk();
        $this->actingAs($this->userWithRole('admin'))->get('/cashier')->assertOk();
        $this->actingAs($this->userWithRole('coordinator'))->get('/cashier')->assertForbidden();
        $this->actingAs($this->userWithRole('manager'))->get('/cashier')->assertForbidden();
    }

    public function test_screening_module_access_is_role_scoped(): void
    {
        $this->actingAs($this->userWithRole('registrar'))->get('/screening')->assertOk();
        $this->actingAs($this->userWithRole('cashier'))->get('/screening')->assertForbidden();
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/login');
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)
            ->delete("/users/{$admin->id}")
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_create_user_with_role(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->post('/users', [
                'name' => 'New Cashier',
                'email' => 'new.cashier@mptvi.com',
                'role' => 'cashier',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHas('success');

        $created = User::where('email', 'new.cashier@mptvi.com')->first();
        $this->assertNotNull($created);
        $this->assertSame('cashier', $created->roleKey());
    }
}
