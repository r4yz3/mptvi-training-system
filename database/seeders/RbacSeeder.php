<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cached roles/permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Capabilities → permissions
        foreach (config('rbac.capabilities') as $cap) {
            Permission::findOrCreate($cap, 'web');
        }

        // Refresh cache so the freshly-created permissions are resolvable by name below.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 2. Roles + assigned caps (admin gets all via Gate::before, but we still
        //    sync every cap to admin so $user->can() works without relying on the bypass)
        $matrix = config('rbac.matrix');
        foreach (config('rbac.roles') as $key => $label) {
            $role = Role::findOrCreate($key, 'web');
            $caps = $key === 'admin' ? config('rbac.capabilities') : ($matrix[$key] ?? []);
            $role->syncPermissions($caps);
        }

        // 3. One staff account per role (idempotent)
        $accounts = [
            ['admin',       'Eleonil Epracse', 'admin@mptvi.com'],
            ['manager',     'Jane Doe',        'secretary@mptvi.com'],
            ['registrar',   'Juan dela Cruz',  'registrar@mptvi.com'],
            ['cashier',     'Jane Smith',      'cashier@mptvi.com'],
            ['coordinator', 'Jhon Doe',        'coordinator@mptvi.com'],
        ];

        foreach ($accounts as [$role, $name, $email]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password')],
            );
            $user->syncRoles([$role]);
        }
    }
}
