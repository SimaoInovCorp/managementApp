<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCompanyLogoRequest;
use App\Http\Requests\Settings\UpdateCompanySettingRequest;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingController extends Controller
{
    /**
     * Show the company settings form.
     * Creates the single settings record on first access if it doesn't exist.
     */
    public function show(): Response
    {
        $settings = CompanySetting::firstOrNew([]);

        return Inertia::render('settings/Company', [
            'settings' => $settings,
            'logoUrl' => $settings->logo_path
                ? route('file.private', ['path' => $settings->logo_path])
                : null,
        ]);
    }

    /**
     * Update the company text settings.
     */
    public function update(UpdateCompanySettingRequest $request): RedirectResponse
    {
        $settings = CompanySetting::firstOrNew([]);
        $settings->fill($request->validated())->save();

        return back()->with('success', 'Company settings saved.');
    }

    /**
     * Upload / replace the company logo (stored on the private disk).
     */
    public function updateLogo(UpdateCompanyLogoRequest $request): RedirectResponse
    {
        $settings = CompanySetting::firstOrNew([]);

        // Remove old logo to avoid orphaned files
        if ($settings->logo_path) {
            Storage::disk('private')->delete($settings->logo_path);
        }

        $settings->logo_path = $request->file('logo')->store('company', 'private');
        $settings->save();

        return back()->with('success', 'Logo updated.');
    }
}
