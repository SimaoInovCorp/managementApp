<?php

use App\Http\Controllers\ProposalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Proposals list page
    Route::get('/proposals', [ProposalController::class, 'index'])
        ->name('proposals.index')
        ->middleware('permission:proposals.read');

    Route::prefix('proposals')->name('proposals.')->group(function () {
        Route::post('/', [ProposalController::class, 'store'])
            ->name('store')
            ->middleware('permission:proposals.create');

        Route::put('/{proposal}', [ProposalController::class, 'update'])
            ->name('update')
            ->middleware('permission:proposals.update');

        Route::delete('/{proposal}', [ProposalController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:proposals.delete');

        // PDF download (GET — streams file, no state change)
        Route::get('/{proposal}/pdf', [ProposalController::class, 'downloadPdf'])
            ->name('pdf')
            ->middleware('permission:proposals.read');

        // Convert closed proposal to a CustomerOrder
        Route::post('/{proposal}/convert', [ProposalController::class, 'convertToOrder'])
            ->name('convert')
            ->middleware('permission:proposals.update');
    });
});
