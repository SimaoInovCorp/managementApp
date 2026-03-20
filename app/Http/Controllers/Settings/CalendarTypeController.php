<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreCalendarTypeRequest;
use App\Http\Requests\Settings\UpdateCalendarTypeRequest;
use App\Http\Resources\CalendarTypeResource;
use App\Models\CalendarType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CalendarTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/CalendarTypes', [
            'types' => CalendarTypeResource::collection(CalendarType::orderBy('name')->get()),
        ]);
    }

    public function store(StoreCalendarTypeRequest $request): RedirectResponse
    {
        CalendarType::create($request->validated());

        return back()->with('success', 'Calendar type created.');
    }

    public function update(UpdateCalendarTypeRequest $request, CalendarType $calendarType): RedirectResponse
    {
        $calendarType->update($request->validated());

        return back()->with('success', 'Calendar type updated.');
    }

    public function destroy(CalendarType $calendarType): RedirectResponse
    {
        if ($calendarType->calendarEvents()->exists()) {
            return back()->with('error', 'Cannot delete: this type is assigned to existing calendar events.');
        }

        $calendarType->delete();

        return back()->with('success', 'Calendar type deleted.');
    }
}
