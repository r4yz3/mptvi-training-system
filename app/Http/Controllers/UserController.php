<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /** Role keys that may be assigned. */
    private function roleOptions(): array
    {
        return collect(config('rbac.roles'))
            ->map(fn ($label, $key) => ['value' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    public function index(Request $request): Response
    {
        $users = User::query()
            ->with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->roleKey(),
                'roleLabel' => $u->roleKey() ? (config('rbac.roles')[$u->roleKey()] ?? $u->roleKey()) : null,
                'is_self' => $u->id === $request->user()->id,
            ]);

        $roleCounts = collect(config('rbac.roles'))
            ->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
                'count' => User::role($key)->count(),
            ])->values();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => $this->roleOptions(),
            'roleCounts' => $roleCounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(config('rbac.roles')))],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $user->syncRoles([$data['role']]);

        return back()->with('success', "User “{$user->name}” created.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(config('rbac.roles')))],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        // Guard: don't let the last admin demote themselves out of admin.
        if ($user->hasRole('admin') && $data['role'] !== 'admin' && $this->adminCount() <= 1) {
            return back()->with('error', 'Cannot change the role of the only Administrator.');
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }
        $user->syncRoles([$data['role']]);

        return back()->with('success', "User “{$user->name}” updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->hasRole('admin') && $this->adminCount() <= 1) {
            return back()->with('error', 'Cannot delete the only Administrator.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "User “{$name}” deleted.");
    }

    private function adminCount(): int
    {
        return User::role('admin')->count();
    }
}
