<?php

namespace App\Observers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * Enriches every activity log entry with request metadata (IP + User-Agent).
 *
 * Registered via AppServiceProvider::boot() on Activity::creating.
 * The data is stored in properties->_meta so it never conflicts with
 * model attribute diffs stored by Spatie in properties->old / properties->attributes.
 */
class ActivityObserver
{
    /**
     * Called before each Activity row is inserted.
     *
     * Skipped in console/queue contexts where there is no HTTP request.
     */
    public function creating(Activity $activity): void
    {
        $request = app(Request::class);

        // Only enrich log entries originating from an HTTP request
        if (! $request->hasSession()) {
            return;
        }

        $properties = $activity->properties->toArray();
        $properties['_meta'] = [
            'ip' => $request->ip(),
            'device' => mb_substr($request->userAgent() ?? '', 0, 200),
        ];

        $activity->properties = collect($properties);
    }
}
