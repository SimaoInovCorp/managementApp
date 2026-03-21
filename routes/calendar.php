<?php

use App\Http\Controllers\CalendarEventController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/calendar', [CalendarEventController::class, 'index'])
        ->name('calendar.index')
        ->middleware('permission:calendar.read');

    // JSON endpoint for FullCalendar dynamic event fetching
    Route::get('/calendar/events', [CalendarEventController::class, 'events'])
        ->name('calendar.events')
        ->middleware('permission:calendar.read');

    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::post('/', [CalendarEventController::class, 'store'])
            ->name('store')
            ->middleware('permission:calendar.create');

        Route::put('/{event}', [CalendarEventController::class, 'update'])
            ->name('update')
            ->middleware('permission:calendar.update');

        Route::delete('/{event}', [CalendarEventController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:calendar.delete');
    });
});
