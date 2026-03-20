<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreCountryRequest;
use App\Http\Requests\Settings\UpdateCountryRequest;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CountryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/Countries', [
            'countries' => CountryResource::collection(Country::orderBy('name')->get()),
        ]);
    }

    public function store(StoreCountryRequest $request): RedirectResponse
    {
        Country::create($request->validated());

        return back()->with('success', 'Country created.');
    }

    public function update(UpdateCountryRequest $request, Country $country): RedirectResponse
    {
        $country->update($request->validated());

        return back()->with('success', 'Country updated.');
    }

    public function destroy(Country $country): RedirectResponse
    {
        if ($country->entities()->exists()) {
            return back()->with('error', 'Cannot delete: this country is assigned to existing entities.');
        }

        $country->delete();

        return back()->with('success', 'Country deleted.');
    }
}
