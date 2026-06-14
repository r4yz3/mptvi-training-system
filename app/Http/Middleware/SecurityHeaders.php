<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds defensive HTTP security headers to every web response. These block
 * common browser-side attacks (clickjacking, MIME-sniffing, referrer leakage)
 * with no visible change for staff. HSTS is only sent over HTTPS so it never
 * locks out the plain-http LAN install.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            // Don't allow the app to be framed by other sites (clickjacking).
            'X-Frame-Options' => 'SAMEORIGIN',
            // Don't let browsers guess content types.
            'X-Content-Type-Options' => 'nosniff',
            // Limit referrer leakage to other origins.
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // Lock down powerful APIs — camera is allowed (2×2 photo capture), the rest off.
            'Permissions-Policy' => 'camera=(self), microphone=(), geolocation=(), payment=()',
            // Legacy header: modern guidance is to disable the buggy XSS auditor.
            'X-XSS-Protection' => '0',
        ];

        foreach ($headers as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        // Only assert HSTS when actually served over HTTPS (production behind TLS).
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
