<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreVatRateRequest;
use App\Http\Requests\Settings\UpdateVatRateRequest;
use App\Http\Resources\VatRateResource;
use App\Models\VatRate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VatRateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/VatRates', [
            'vatRates' => VatRateResource::collection(VatRate::orderBy('rate')->get()),
        ]);
    }

    public function store(StoreVatRateRequest $request): RedirectResponse
    {
        VatRate::create($request->validated());

        return back()->with('success', 'VAT rate created.');
    }

    public function update(UpdateVatRateRequest $request, VatRate $vatRate): RedirectResponse
    {
        $vatRate->update($request->validated());

        return back()->with('success', 'VAT rate updated.');
    }

    public function destroy(VatRate $vatRate): RedirectResponse
    {
        if ($vatRate->articles()->exists()) {
            return back()->with('error', 'Cannot delete: this VAT rate is assigned to existing articles.');
        }

        $vatRate->delete();

        return back()->with('success', 'VAT rate deleted.');
    }
}
