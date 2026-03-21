<?php

use App\Http\Controllers\CustomerOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Customer orders list page
    Route::get('/customer-orders', [CustomerOrderController::class, 'index'])
        ->name('customer_orders.index')
        ->middleware('permission:customer_orders.read');

    Route::prefix('customer-orders')->name('customer_orders.')->group(function () {
        Route::post('/', [CustomerOrderController::class, 'store'])
            ->name('store')
            ->middleware('permission:customer_orders.create');

        Route::put('/{order}', [CustomerOrderController::class, 'update'])
            ->name('update')
            ->middleware('permission:customer_orders.update');

        Route::delete('/{order}', [CustomerOrderController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:customer_orders.delete');

        // PDF download (GET — streams file, no state change)
        Route::get('/{order}/pdf', [CustomerOrderController::class, 'downloadPdf'])
            ->name('pdf')
            ->middleware('permission:customer_orders.read');

        // Convert closed order to supplier orders
        Route::post('/{order}/convert', [CustomerOrderController::class, 'convertToSupplierOrders'])
            ->name('convert')
            ->middleware('permission:customer_orders.update');
    });
});
