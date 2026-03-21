<?php

use App\Http\Controllers\CustomerAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/financial/customer-accounts', [CustomerAccountController::class, 'index'])
        ->name('financial.customer_accounts.index')
        ->middleware('permission:financial_current_account.read');

    Route::post('/financial/customer-accounts', [CustomerAccountController::class, 'store'])
        ->name('financial.customer_accounts.store')
        ->middleware('permission:financial_current_account.create');
});
