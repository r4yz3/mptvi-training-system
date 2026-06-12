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

        // separation of duties: only cashier records payments
        $this->assertTrue($cashier->can('payment.record'));
        $this->assertFalse($manager->can('payment.record'));
        $this->assertFalse($registrar->can('payment.record'));

        // pii.view = admin + manager + registrar (NOT cashier/coordinator)
        $this->assertTrue($manager->can('pii.view'));
        $this->assertTrue($registrar->can('pii.view'));
        $this->assertFalse($cashier->can('pii.view'));
        $this->assertFalse($coordinator->can('pii.view'));

        // finance.view = admin only
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

    public function test_non_admin_blocked_from_users_module(): void
    {
        foreach (['manager', 'registrar', 'cashier', 'coordinator'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get('/users')
                ->assertForbidden();
        }
    }

    public function test_cashier_module_access_is_role_scoped(): void
    {
        // cashier + admin may enter; others 403
        $this->actingAs($this->userWithRole('cashier'))->get('/cashier')->assertOk();
        $this->actingAs($this->userWithRole('admin'))->get('/cashier')->assertOk();
        $this->actingAs($this->userWithRole('coordinator'))->get('/cashier')->assertForbidden();
        $this->actingAs($this->userWithRole('registrar'))->get('/cashier')->assertForbidden();
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
                'email' => 'new.cashier@mptvi.test',
                'role' => 'cashier',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHas('success');

        $created = User::where('email', 'new.cashier@mptvi.test')->first();
        $this->assertNotNull($created);
        $this->assertSame('cashier', $created->roleKey());
    }
}
