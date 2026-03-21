<?php

use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Work orders list page
    Route::get('/work-orders', [WorkOrderController::class, 'index'])
        ->name('work_orders.index')
        ->middleware('permission:work_orders.read');

    Route::prefix('work-orders')->name('work_orders.')->group(function () {
        Route::post('/', [WorkOrderController::class, 'store'])
            ->name('store')
            ->middleware('permission:work_orders.create');

        Route::put('/{order}', [WorkOrderController::class, 'update'])
            ->name('update')
            ->middleware('permission:work_orders.update');

        Route::delete('/{order}', [WorkOrderController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:work_orders.delete');
    });
});
