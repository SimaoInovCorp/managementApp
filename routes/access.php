<?php

use App\Http\Controllers\Access\PermissionGroupController;
use App\Http\Controllers\Access\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('access')->name('access.')->group(function () {

    // Permission Groups (Spatie Roles)
    Route::get('permissions', [PermissionGroupController::class, 'index'])->name('permissions.index')->middleware('permission:access_permissions.read');
    Route::post('permissions', [PermissionGroupController::class, 'store'])->name('permissions.store')->middleware('permission:access_permissions.create');
    Route::put('permissions/{role}', [PermissionGroupController::class, 'update'])->name('permissions.update')->middleware('permission:access_permissions.update');
    Route::delete('permissions/{role}', [PermissionGroupController::class, 'destroy'])->name('permissions.destroy')->middleware('permission:access_permissions.delete');

    // User Management
    Route::get('users', [UserManagementController::class, 'index'])->name('users.index')->middleware('permission:access_users.read');
    Route::post('users', [UserManagementController::class, 'store'])->name('users.store')->middleware('permission:access_users.create');
    Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update')->middleware('permission:access_users.update');
    Route::patch('users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status')->middleware('permission:access_users.update');
    Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy')->middleware('permission:access_users.delete');
});
