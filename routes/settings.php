<?php

use App\Http\Controllers\Settings\ArticleController;
use App\Http\Controllers\Settings\CalendarActionController;
use App\Http\Controllers\Settings\CalendarTypeController;
use App\Http\Controllers\Settings\CompanySettingController;
use App\Http\Controllers\Settings\ContactRoleController;
use App\Http\Controllers\Settings\CountryController;
use App\Http\Controllers\Settings\LogController;
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
        Route::get('countries', [CountryController::class, 'index'])->name('countries.index')->middleware('permission:settings_countries.read');
        Route::post('countries', [CountryController::class, 'store'])->name('countries.store')->middleware('permission:settings_countries.create');
        Route::put('countries/{country}', [CountryController::class, 'update'])->name('countries.update')->middleware('permission:settings_countries.update');
        Route::delete('countries/{country}', [CountryController::class, 'destroy'])->name('countries.destroy')->middleware('permission:settings_countries.delete');

        // Contact Roles
        Route::get('contact-roles', [ContactRoleController::class, 'index'])->name('contact-roles.index')->middleware('permission:settings_roles.read');
        Route::post('contact-roles', [ContactRoleController::class, 'store'])->name('contact-roles.store')->middleware('permission:settings_roles.create');
        Route::put('contact-roles/{contactRole}', [ContactRoleController::class, 'update'])->name('contact-roles.update')->middleware('permission:settings_roles.update');
        Route::delete('contact-roles/{contactRole}', [ContactRoleController::class, 'destroy'])->name('contact-roles.destroy')->middleware('permission:settings_roles.delete');

        // Calendar Types
        Route::get('calendar-types', [CalendarTypeController::class, 'index'])->name('calendar-types.index')->middleware('permission:settings_calendar_types.read');
        Route::post('calendar-types', [CalendarTypeController::class, 'store'])->name('calendar-types.store')->middleware('permission:settings_calendar_types.create');
        Route::put('calendar-types/{calendarType}', [CalendarTypeController::class, 'update'])->name('calendar-types.update')->middleware('permission:settings_calendar_types.update');
        Route::delete('calendar-types/{calendarType}', [CalendarTypeController::class, 'destroy'])->name('calendar-types.destroy')->middleware('permission:settings_calendar_types.delete');

        // Calendar Actions
        Route::get('calendar-actions', [CalendarActionController::class, 'index'])->name('calendar-actions.index')->middleware('permission:settings_calendar_actions.read');
        Route::post('calendar-actions', [CalendarActionController::class, 'store'])->name('calendar-actions.store')->middleware('permission:settings_calendar_actions.create');
        Route::put('calendar-actions/{calendarAction}', [CalendarActionController::class, 'update'])->name('calendar-actions.update')->middleware('permission:settings_calendar_actions.update');
        Route::delete('calendar-actions/{calendarAction}', [CalendarActionController::class, 'destroy'])->name('calendar-actions.destroy')->middleware('permission:settings_calendar_actions.delete');

        // VAT Rates
        Route::get('vat-rates', [VatRateController::class, 'index'])->name('vat-rates.index')->middleware('permission:settings_vat.read');
        Route::post('vat-rates', [VatRateController::class, 'store'])->name('vat-rates.store')->middleware('permission:settings_vat.create');
        Route::put('vat-rates/{vatRate}', [VatRateController::class, 'update'])->name('vat-rates.update')->middleware('permission:settings_vat.update');
        Route::delete('vat-rates/{vatRate}', [VatRateController::class, 'destroy'])->name('vat-rates.destroy')->middleware('permission:settings_vat.delete');

        // Company Settings (single-row)
        Route::get('company', [CompanySettingController::class, 'show'])->name('company.show')->middleware('permission:settings_company.read');
        Route::put('company', [CompanySettingController::class, 'update'])->name('company.update')->middleware('permission:settings_company.update');
        Route::post('company/logo', [CompanySettingController::class, 'updateLogo'])->name('company.logo')->middleware('permission:settings_company.update');

        // Activity Logs (read-only)
        Route::get('logs', [LogController::class, 'index'])->name('logs.index')->middleware('permission:settings_logs.read');

        // Articles / Product Catalogue
        Route::get('articles', [ArticleController::class, 'index'])->name('articles.index')->middleware('permission:settings_articles.read');
        Route::post('articles', [ArticleController::class, 'store'])->name('articles.store')->middleware('permission:settings_articles.create');
        Route::put('articles/{article}', [ArticleController::class, 'update'])->name('articles.update')->middleware('permission:settings_articles.update');
        Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy')->middleware('permission:settings_articles.delete');
    });
});
