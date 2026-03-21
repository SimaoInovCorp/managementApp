<?php

use App\Http\Controllers\BankAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/financial/bank-accounts', [BankAccountController::class, 'index'])
        ->name('financial.bank_accounts.index')
        ->middleware('permission:financial_bank.read');

    Route::prefix('financial/bank-accounts')->name('financial.bank_accounts.')->group(function () {
        Route::post('/', [BankAccountController::class, 'store'])
            ->name('store')
            ->middleware('permission:financial_bank.create');

        Route::put('/{account}', [BankAccountController::class, 'update'])
            ->name('update')
            ->middleware('permission:financial_bank.update');

        Route::delete('/{account}', [BankAccountController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:financial_bank.delete');
    });
});
