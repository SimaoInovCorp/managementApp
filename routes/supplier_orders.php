<?php

use App\Http\Controllers\SupplierOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Supplier orders list page
    Route::get('/supplier-orders', [SupplierOrderController::class, 'index'])
        ->name('supplier_orders.index')
        ->middleware('permission:supplier_orders.read');

    Route::prefix('supplier-orders')->name('supplier_orders.')->group(function () {
        Route::post('/', [SupplierOrderController::class, 'store'])
            ->name('store')
            ->middleware('permission:supplier_orders.create');

        Route::put('/{order}', [SupplierOrderController::class, 'update'])
            ->name('update')
            ->middleware('permission:supplier_orders.update');

        Route::delete('/{order}', [SupplierOrderController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:supplier_orders.delete');

        // PDF download (GET — streams file, no state change)
        Route::get('/{order}/pdf', [SupplierOrderController::class, 'downloadPdf'])
            ->name('pdf')
            ->middleware('permission:supplier_orders.read');
    });
});
