<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreCalendarActionRequest;
use App\Http\Requests\Settings\UpdateCalendarActionRequest;
use App\Http\Resources\CalendarActionResource;
use App\Models\CalendarAction;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CalendarActionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/CalendarActions', [
            'actions' => CalendarActionResource::collection(CalendarAction::orderBy('name')->get()),
        ]);
    }

    public function store(StoreCalendarActionRequest $request): RedirectResponse
    {
        CalendarAction::create($request->validated());

        return back()->with('success', 'Calendar action created.');
    }

    public function update(UpdateCalendarActionRequest $request, CalendarAction $calendarAction): RedirectResponse
    {
        $calendarAction->update($request->validated());

        return back()->with('success', 'Calendar action updated.');
    }

    public function destroy(CalendarAction $calendarAction): RedirectResponse
    {
        if ($calendarAction->calendarEvents()->exists()) {
            return back()->with('error', 'Cannot delete: this action is assigned to existing calendar events.');
        }

        $calendarAction->delete();

        return back()->with('success', 'Calendar action deleted.');
    }
}
