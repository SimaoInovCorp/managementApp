<?php

use App\Http\Controllers\Settings\CalendarActionController;
use App\Http\Controllers\Settings\CalendarTypeController;
use App\Http\Controllers\Settings\CompanySettingController;
use App\Http\Controllers\Settings\ContactRoleController;
use App\Http\Controllers\Settings\CountryController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\Settings\VatRateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    // -------------------------------------------------------------------------
    // Phase 3 — Config tables (countries, roles, calendar, VAT, company)
    // -------------------------------------------------------------------------
    Route::prefix('settings/config')->name('settings.config.')->group(function () {

        // Countries
        Route::get('countries', [CountryController::class, 'index'])->name('countries.index');
        Route::post('countries', [CountryController::class, 'store'])->name('countries.store');
        Route::put('countries/{country}', [CountryController::class, 'update'])->name('countries.update');
        Route::delete('countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy');

        // Contact Roles
        Route::get('contact-roles', [ContactRoleController::class, 'index'])->name('contact-roles.index');
        Route::post('contact-roles', [ContactRoleController::class, 'store'])->name('contact-roles.store');
        Route::put('contact-roles/{contactRole}', [ContactRoleController::class, 'update'])->name('contact-roles.update');
        Route::delete('contact-roles/{contactRole}', [ContactRoleController::class, 'destroy'])->name('contact-roles.destroy');

        // Calendar Types
        Route::get('calendar-types', [CalendarTypeController::class, 'index'])->name('calendar-types.index');
        Route::post('calendar-types', [CalendarTypeController::class, 'store'])->name('calendar-types.store');
        Route::put('calendar-types/{calendarType}', [CalendarTypeController::class, 'update'])->name('calendar-types.update');
        Route::delete('calendar-types/{calendarType}', [CalendarTypeController::class, 'destroy'])->name('calendar-types.destroy');

        // Calendar Actions
        Route::get('calendar-actions', [CalendarActionController::class, 'index'])->name('calendar-actions.index');
        Route::post('calendar-actions', [CalendarActionController::class, 'store'])->name('calendar-actions.store');
        Route::put('calendar-actions/{calendarAction}', [CalendarActionController::class, 'update'])->name('calendar-actions.update');
        Route::delete('calendar-actions/{calendarAction}', [CalendarActionController::class, 'destroy'])->name('calendar-actions.destroy');

        // VAT Rates
        Route::get('vat-rates', [VatRateController::class, 'index'])->name('vat-rates.index');
        Route::post('vat-rates', [VatRateController::class, 'store'])->name('vat-rates.store');
        Route::put('vat-rates/{vatRate}', [VatRateController::class, 'update'])->name('vat-rates.update');
        Route::delete('vat-rates/{vatRate}', [VatRateController::class, 'destroy'])->name('vat-rates.destroy');

        // Company Settings (single-row)
        Route::get('company', [CompanySettingController::class, 'show'])->name('company.show');
        Route::put('company', [CompanySettingController::class, 'update'])->name('company.update');
        Route::post('company/logo', [CompanySettingController::class, 'updateLogo'])->name('company.logo');
    });
});
