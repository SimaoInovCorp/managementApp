<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * Displays the read-only activity log viewer.
 *
 * Supports optional filters: date_from, date_to, user_id (causer_id).
 * Each entry exposes IP and device metadata stored by ActivityObserver.
 */
class LogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Activity::query()
            ->with('causer:id,name')
            ->orderByDesc('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('user_id')) {
            $query->where('causer_type', User::class)
                ->where('causer_id', $request->user_id);
        }

        $logs = $query->paginate(50)->through(fn (Activity $a) => [
            'id' => $a->id,
            'log_name' => $a->log_name,
            'description' => $a->description,
            'event' => $a->event,
            'subject_type' => $a->subject_type ? class_basename($a->subject_type) : null,
            'subject_id' => $a->subject_id,
            'causer' => $a->causer?->only('id', 'name'),
            'properties' => $a->properties,
            'ip' => data_get($a->properties, '_meta.ip'),
            'device' => data_get($a->properties, '_meta.device'),
            'created_at' => $a->created_at?->format('Y-m-d H:i:s'),
        ]);

        // Pass all users for the filter dropdown (id + name only)
        $users = User::orderBy('name')->get(['id', 'name']);

        return Inertia::render('settings/Logs', [
            'logs' => $logs,
            'filters' => $request->only('date_from', 'date_to', 'user_id'),
            'users' => $users,
        ]);
    }
}
