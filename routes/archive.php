<?php

use App\Http\Controllers\DigitalArchiveController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/archive', [DigitalArchiveController::class, 'index'])
        ->name('archive.index')
        ->middleware('permission:digital_archive.read');

    Route::prefix('archive')->name('archive.')->group(function () {
        Route::post('/', [DigitalArchiveController::class, 'store'])
            ->name('store')
            ->middleware('permission:digital_archive.create');

        Route::get('/{archive}/download', [DigitalArchiveController::class, 'show'])
            ->name('show')
            ->middleware('permission:digital_archive.read');

        Route::delete('/{archive}', [DigitalArchiveController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:digital_archive.delete');
    });
});
