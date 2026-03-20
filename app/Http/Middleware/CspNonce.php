<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generates a cryptographically-random nonce per request and binds it to the
 * application container so Blade templates and SecurityHeaders can read it.
 * Also registers the nonce with the Vite helper so @vite() script/link tags
 * include the nonce attribute (required for the CSP 'strict-dynamic' policy).
 */
class CspNonce
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate 16-byte random nonce, base64-encode to get a URL-safe string
        $nonce = base64_encode(random_bytes(16));
        app()->instance('csp-nonce', $nonce);

        // Tell the Vite helper to include this nonce on every <script>/<link>
        // tag it generates — required because 'strict-dynamic' in CSP ignores
        // 'self' and only trusts nonce-bearing or dynamically-loaded scripts.
        Vite::useCspNonce($nonce);

        return $next($request);
    }
}
