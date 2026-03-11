<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\VatRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VatRateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/VatRates', [
            'vatRates' => VatRate::orderBy('rate')->get(['id', 'name', 'rate']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:vat_rates,name'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        return response()->json(VatRate::create($validated), 201);
    }

    public function update(Request $request, VatRate $vatRate): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:vat_rates,name,' . $vatRate->id],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $vatRate->update($validated);

        return response()->json($vatRate);
    }

    public function destroy(VatRate $vatRate): JsonResponse
    {
        abort_if($vatRate->articles()->exists(), 422, 'Cannot delete: VAT rate is in use.');

        $vatRate->delete();

        return response()->json(null, 204);
    }
}
