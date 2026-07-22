<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyLicense
{
    public function handle(Request $request, Closure $next): Response
    {
        $license = app(LicenseService::class)->check();

        if (! $license['valid']) {
            return response()->view('license-invalid', ['reason' => $license['reason']], 403);
        }

        return $next($request);
    }
}
