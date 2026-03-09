<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds strict security HTTP headers to every response.
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Content-Security-Policy — use per-request nonce if available
        $nonce = app()->bound('csp-nonce') ? app('csp-nonce') : null;
        $scriptSrc = $nonce
            ? "'nonce-{$nonce}' 'strict-dynamic'"
            : "'self' 'unsafe-inline'";

        // Allow WebSocket connections for Vite HMR in non-production environments
        $connectSrc = app()->environment('production')
            ? "'self'"
            : "'self' ws://localhost:* wss://localhost:*";

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' {$scriptSrc}",
            "style-src 'self' 'unsafe-inline'",  // unsafe-inline needed for Vite-injected styles
            "img-src 'self' data: blob:",
            "font-src 'self' https://fonts.bunny.net",
            "connect-src {$connectSrc}",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
