<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Contact list view
    Route::get('/contacts', [ContactController::class, 'index'])
        ->name('contacts.index')
        ->middleware('permission:contacts.read');

    // Contact CRUD
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::post('/', [ContactController::class, 'store'])->name('store')->middleware('permission:contacts.create');
        Route::put('/{contact}', [ContactController::class, 'update'])->name('update')->middleware('permission:contacts.update');
        Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy')->middleware('permission:contacts.delete');
    });
});
