<?php

use App\Http\Controllers\EntityController;
use App\Http\Controllers\ViesLookupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Client and Supplier list views — inject currentType via route defaults
    Route::get('/clients', [EntityController::class, 'index'])
        ->defaults('currentType', 'client')
        ->name('entities.clients')
        ->middleware('permission:clients.read');

    Route::get('/suppliers', [EntityController::class, 'index'])
        ->defaults('currentType', 'supplier')
        ->name('entities.suppliers')
        ->middleware('permission:suppliers.read');

    // Entity CRUD (type-agnostic)
    Route::prefix('entities')->name('entities.')->group(function () {
        Route::post('/', [EntityController::class, 'store'])->name('store')->middleware('permission:clients.create|suppliers.create');
        Route::put('/{entity}', [EntityController::class, 'update'])->name('update')->middleware('permission:clients.update|suppliers.update');
        Route::delete('/{entity}', [EntityController::class, 'destroy'])->name('destroy')->middleware('permission:clients.delete|suppliers.delete');
    });

    // VIES VAT lookup proxy (no permission required beyond auth)
    Route::get('/vies/lookup', [ViesLookupController::class, 'lookup'])->name('vies.lookup');
});
