<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CalendarAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarActionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/CalendarActions', [
            'actions' => CalendarAction::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:calendar_actions,name'],
        ]);

        return response()->json(CalendarAction::create($validated), 201);
    }

    public function update(Request $request, CalendarAction $calendarAction): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:calendar_actions,name,' . $calendarAction->id],
        ]);

        $calendarAction->update($validated);

        return response()->json($calendarAction);
    }

    public function destroy(CalendarAction $calendarAction): JsonResponse
    {
        abort_if($calendarAction->calendarEvents()->exists(), 422, 'Cannot delete: action is in use.');

        $calendarAction->delete();

        return response()->json(null, 204);
    }
}
