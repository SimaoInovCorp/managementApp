<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CalendarType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/CalendarTypes', [
            'types' => CalendarType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:calendar_types,name'],
        ]);

        return response()->json(CalendarType::create($validated), 201);
    }

    public function update(Request $request, CalendarType $calendarType): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:calendar_types,name,' . $calendarType->id],
        ]);

        $calendarType->update($validated);

        return response()->json($calendarType);
    }

    public function destroy(CalendarType $calendarType): JsonResponse
    {
        abort_if($calendarType->calendarEvents()->exists(), 422, 'Cannot delete: type is in use.');

        $calendarType->delete();

        return response()->json(null, 204);
    }
}
