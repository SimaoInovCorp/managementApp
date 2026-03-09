<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generates a cryptographically-random nonce per request and binds it to the
 * application container so Blade templates and SecurityHeaders can read it.
 */
class CspNonce
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate 16-byte random nonce, base64-encode to get a URL-safe string
        $nonce = base64_encode(random_bytes(16));
        app()->instance('csp-nonce', $nonce);

        return $next($request);
    }
}
