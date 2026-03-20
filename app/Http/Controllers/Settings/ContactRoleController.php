<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreContactRoleRequest;
use App\Http\Requests\Settings\UpdateContactRoleRequest;
use App\Http\Resources\ContactRoleResource;
use App\Models\ContactRole;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactRoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/ContactRoles', [
            'roles' => ContactRoleResource::collection(ContactRole::orderBy('name')->get()),
        ]);
    }

    public function store(StoreContactRoleRequest $request): RedirectResponse
    {
        ContactRole::create($request->validated());

        return back()->with('success', 'Contact role created.');
    }

    public function update(UpdateContactRoleRequest $request, ContactRole $contactRole): RedirectResponse
    {
        $contactRole->update($request->validated());

        return back()->with('success', 'Contact role updated.');
    }

    public function destroy(ContactRole $contactRole): RedirectResponse
    {
        if ($contactRole->contacts()->exists()) {
            return back()->with('error', 'Cannot delete: this role is assigned to existing contacts.');
        }

        $contactRole->delete();

        return back()->with('success', 'Contact role deleted.');
    }
}
