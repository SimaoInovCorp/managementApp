<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCalendarEventRequest;
use App\Http\Requests\UpdateCalendarEventRequest;
use App\Http\Resources\CalendarEventResource;
use App\Models\CalendarAction;
use App\Models\CalendarEvent;
use App\Models\CalendarType;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages calendar events with FullCalendar-compatible JSON output.
 */
class CalendarEventController extends Controller
{
    /**
     * Render the calendar page (Inertia) with initial data.
     */
    public function index(Request $request): Response
    {
        $query = CalendarEvent::with([
            'user:id,name',
            'entity:id,name',
            'type:id,name',
            'action:id,name',
        ])->orderBy('date')->orderBy('time');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->integer('entity_id'));
        }

        $events = $query->get();
        $users = User::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $clients = Entity::whereIn('type', ['client', 'both'])->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $types = CalendarType::orderBy('name')->get(['id', 'name']);
        $actions = CalendarAction::orderBy('name')->get(['id', 'name']);

        return Inertia::render('calendar/Index', [
            'events' => CalendarEventResource::collection($events),
            'users' => $users,
            'clients' => $clients,
            'types' => $types,
            'actions' => $actions,
            'selectedUserId' => $request->integer('user_id') ?: null,
            'selectedEntityId' => $request->integer('entity_id') ?: null,
        ]);
    }

    /**
     * Return events as JSON for FullCalendar dynamic fetching.
     */
    public function events(Request $request): JsonResponse
    {
        $query = CalendarEvent::with(['entity:id,name', 'type:id,name'])
            ->orderBy('date');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->integer('entity_id'));
        }

        return response()->json(
            CalendarEventResource::collection($query->get())
        );
    }

    /**
     * Store a new calendar event.
     */
    public function store(StoreCalendarEventRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        CalendarEvent::create($data);

        return back()->with('success', 'Event created successfully.');
    }

    /**
     * Update an existing calendar event.
     */
    public function update(UpdateCalendarEventRequest $request, CalendarEvent $event): RedirectResponse
    {
        $event->update($request->validated());

        return back()->with('success', 'Event updated successfully.');
    }

    /**
     * Delete a calendar event.
     */
    public function destroy(CalendarEvent $event): RedirectResponse
    {
        $event->delete();

        return back()->with('success', 'Event deleted.');
    }
}
