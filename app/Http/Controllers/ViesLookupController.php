<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side proxy for the VIES (EU VAT Information Exchange System) REST API.
 * All calls go through this controller so that the VIES endpoint is never
 * exposed directly to the browser (security & rate-limit handling in one place).
 *
 * VIES REST API: POST https://ec.europa.eu/taxation_customs/vies/rest-api//check-vat-number
 */
class ViesLookupController extends Controller
{
    private const VIES_URL = 'https://ec.europa.eu/taxation_customs/vies/rest-api//check-vat-number';

    /**
     * Look up a VAT number via the VIES REST API.
     *
     * Query param: ?vat=PT507957547
     *   - If the first two characters are letters, they are used as the country code.
     *   - Otherwise, the full string is sent as the VAT number with country code "PT" as default.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate(['vat' => ['required', 'string', 'max:20']]);

        $vat = strtoupper(trim($request->vat));

        // Split country code (first 2 alpha chars) and numeric part
        if (preg_match('/^([A-Z]{2})(.+)$/', $vat, $matches)) {
            $countryCode = $matches[1];
            $vatNumber = $matches[2];
        } else {
            $countryCode = 'PT'; // default to Portugal
            $vatNumber = $vat;
        }

        try {
            $response = Http::timeout(10)->post(self::VIES_URL, [
                'countryCode' => $countryCode,
                'vatNumber' => $vatNumber,
            ]);

            if (! $response->successful()) {
                return response()->json(['valid' => false, 'message' => 'VIES service unavailable.']);
            }

            $data = $response->json();

            return response()->json([
                'valid' => (bool) ($data['valid'] ?? false),
                'name' => $data['name'] ?? null,
                'address' => $data['address'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('VIES lookup failed', ['vat' => $vat, 'error' => $e->getMessage()]);

            return response()->json(['valid' => false, 'message' => 'Could not reach VIES service.']);
        }
    }
}
