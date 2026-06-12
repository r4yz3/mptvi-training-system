<?php

namespace App\Support;

use App\Models\User;

class Rbac
{
    /** Nav modules visible to the given role key. */
    public static function modulesFor(?string $roleKey): array
    {
        return array_values(array_filter(
            config('rbac.modules'),
            fn ($m) => in_array($roleKey, $m['roles'], true),
        ));
    }

    /** May this role open the given module id? */
    public static function canAccessModule(?string $roleKey, string $moduleId): bool
    {
        foreach (config('rbac.modules') as $m) {
            if ($m['id'] === $moduleId) {
                return in_array($roleKey, $m['roles'], true);
            }
        }

        return false;
    }

    /** cap => bool map for the user (used to gate UI in React). */
    public static function capsFor(User $user): array
    {
        $caps = [];
        foreach (config('rbac.capabilities') as $cap) {
            $caps[$cap] = $user->can($cap);
        }

        return $caps;
    }
}
