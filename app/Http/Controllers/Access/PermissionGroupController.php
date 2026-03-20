<?php

namespace App\Http\Controllers\Access;

use App\Concerns\PermissionConstants;
use App\Http\Controllers\Controller;
use App\Http\Requests\Access\StorePermissionGroupRequest;
use App\Http\Requests\Access\UpdatePermissionGroupRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Manages permission groups (Spatie Roles).
 *
 * Each role carries a set of permissions chosen from the full CRUD matrix.
 */
class PermissionGroupController extends Controller
{
    /**
     * Display all permission groups with their permission matrix.
     */
    public function index(): Response
    {
        $roles = Role::withCount('users')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('access/Permissions', [
            'roles' => $roles,
            'allMenus' => PermissionConstants::MENUS,
            'menuLabels' => PermissionConstants::MENU_LABELS,
            'allActions' => PermissionConstants::ACTIONS,
        ]);
    }

    /**
     * Create a new permission group.
     */
    public function store(StorePermissionGroupRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', "Permission group \"{$role->name}\" created.");
    }

    /**
     * Update an existing permission group name and permissions.
     */
    public function update(UpdatePermissionGroupRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', "Permission group \"{$role->name}\" updated.");
    }

    /**
     * Delete a permission group.
     *
     * Blocked if the role is the built-in Administrator or has users assigned.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'Administrator') {
            return back()->with('error', 'The Administrator role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete: this role is assigned to one or more users.');
        }

        $role->delete();

        return back()->with('success', 'Permission group deleted.');
    }
}
