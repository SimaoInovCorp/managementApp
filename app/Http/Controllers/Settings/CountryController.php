<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CountryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/Countries', [
            'countries' => Country::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'size:2', 'uppercase', 'unique:countries,code'],
        ]);

        return response()->json(Country::create($validated), 201);
    }

    public function update(Request $request, Country $country): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'size:2', 'uppercase', 'unique:countries,code,' . $country->id],
        ]);

        $country->update($validated);

        return response()->json($country);
    }

    public function destroy(Country $country): JsonResponse
    {
        // Prevent deletion if entities reference this country
        abort_if($country->entities()->exists(), 422, 'Cannot delete: country is in use.');

        $country->delete();

        return response()->json(null, 204);
    }
}
