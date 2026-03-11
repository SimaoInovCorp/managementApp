<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ContactRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactRoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/ContactRoles', [
            'roles' => ContactRole::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:contact_roles,name'],
        ]);

        return response()->json(ContactRole::create($validated), 201);
    }

    public function update(Request $request, ContactRole $contactRole): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:contact_roles,name,' . $contactRole->id],
        ]);

        $contactRole->update($validated);

        return response()->json($contactRole);
    }

    public function destroy(ContactRole $contactRole): JsonResponse
    {
        abort_if($contactRole->contacts()->exists(), 422, 'Cannot delete: role is in use.');

        $contactRole->delete();

        return response()->json(null, 204);
    }
}
