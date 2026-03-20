<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Http\Requests\Access\StoreUserManagementRequest;
use App\Http\Requests\Access\UpdateUserManagementRequest;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Manages application users and their role assignments.
 */
class UserManagementController extends Controller
{
    /**
     * List all users with their assigned roles.
     */
    public function index(): Response
    {
        $users = User::with('roles:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status', 'created_at']);

        $roles = Role::orderBy('name')->get(['id', 'name']);

        return Inertia::render('access/Users', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * Create a new user, assign a role, and send a welcome email.
     */
    public function store(StoreUserManagementRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Generate a secure temporary password
        $temporaryPassword = Str::password(12);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($temporaryPassword),
            'status' => 'active',
        ]);

        if (! empty($validated['role_id'])) {
            $role = Role::findById($validated['role_id']);
            $user->assignRole($role);
        }

        // Send welcome email with temporary password
        $user->notify(new WelcomeNotification($temporaryPassword));

        return back()->with('success', "User \"{$user->name}\" created. A welcome email has been sent.");
    }

    /**
     * Update user details and role assignment.
     */
    public function update(UpdateUserManagementRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        // Sync role — remove existing roles and assign the new one
        $user->syncRoles(
            ! empty($validated['role_id'])
                ? [Role::findById($validated['role_id'])]
                : []
        );

        return back()->with('success', "User \"{$user->name}\" updated.");
    }

    /**
     * Toggle the user's active/inactive status.
     *
     * The authenticated user cannot deactivate their own account.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot change your own status.');
        }

        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', "User \"{$user->name}\" is now {$user->status}.");
    }

    /**
     * Delete a user.
     *
     * The authenticated user cannot delete themselves.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account here.');
        }

        $user->delete();

        return back()->with('success', "User \"{$user->name}\" deleted.");
    }
}
