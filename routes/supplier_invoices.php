<?php

use App\Http\Controllers\SupplierInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/financial/supplier-invoices', [SupplierInvoiceController::class, 'index'])
        ->name('financial.supplier_invoices.index')
        ->middleware('permission:financial_invoices.read');

    Route::prefix('financial/supplier-invoices')->name('financial.supplier_invoices.')->group(function () {
        Route::post('/', [SupplierInvoiceController::class, 'store'])
            ->name('store')
            ->middleware('permission:financial_invoices.create');

        Route::put('/{invoice}', [SupplierInvoiceController::class, 'update'])
            ->name('update')
            ->middleware('permission:financial_invoices.update');

        Route::delete('/{invoice}', [SupplierInvoiceController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:financial_invoices.delete');

        Route::post('/{invoice}/pay', [SupplierInvoiceController::class, 'sendPaymentConfirmation'])
            ->name('pay')
            ->middleware('permission:financial_invoices.update');
    });
});
