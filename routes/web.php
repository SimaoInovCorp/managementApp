<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileAccessController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Private file access — all private files served through this authenticated route
    Route::get('private/{path}', [FileAccessController::class, 'show'])
        ->where('path', '.*')
        ->name('file.private');
});

require __DIR__.'/settings.php';
require __DIR__.'/access.php';
require __DIR__.'/entities.php';
require __DIR__.'/contacts.php';
require __DIR__.'/proposals.php';
require __DIR__.'/customer_orders.php';
require __DIR__.'/supplier_orders.php';
require __DIR__.'/work_orders.php';
require __DIR__.'/bank_accounts.php';
require __DIR__.'/customer_accounts.php';
require __DIR__.'/supplier_invoices.php';
require __DIR__.'/calendar.php';
require __DIR__.'/archive.php';
