<?php

namespace App\Http\Middleware;

use App\Support\Rbac;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    /**
     * Backstop for nav-level RBAC: blocks a role from entering a module's routes
     * even if the link were forged. Usage: ->middleware('module:cashier')
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $roleKey = $request->user()?->roleKey();

        abort_unless(Rbac::canAccessModule($roleKey, $module), 403, 'You do not have access to this module.');

        return $next($request);
    }
}
