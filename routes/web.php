<?php

use App\Http\Controllers\FileAccessController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Private file access — all private files served through this authenticated route
    Route::get('private/{path}', [FileAccessController::class, 'show'])
        ->where('path', '.*')
        ->name('file.private');
});

require __DIR__.'/settings.php';
