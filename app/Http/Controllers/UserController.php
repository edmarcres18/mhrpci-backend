<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use App\Notifications\UserInvitation;
use App\UserRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // Only Admin and System Admin can access user management
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access user management.');
        }

        $search = (string) $request->query('search', '');
        $perPage = (int) $request->query('perPage', 10);
        $allowed = [10, 25, 50, 100];
        if (! in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $query = User::query();

        // Admin users cannot see System Admin accounts
        if ($currentUser->isAdmin()) {
            $query->where('role', '!=', UserRole::SYSTEM_ADMIN->value);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->value,
                    'role_display' => $user->role?->displayName(),
                    'created_at' => optional($user->created_at)->toDateTimeString(),
                ];
            });

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'perPage' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        // Only Admin and System Admin can access user management
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access user management.');
        }

        $roles = collect(UserRole::all())
            ->filter(function ($role) use ($currentUser) {
                // Admin users cannot create System Admin accounts
                if ($currentUser->isAdmin() && $role === UserRole::SYSTEM_ADMIN) {
                    return false;
                }

                return true;
            })
            ->map(function ($role) {
                return [
                    'value' => $role->value,
                    'label' => $role->displayName(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Users/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Only Admin and System Admin can access user management
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access user management.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:'.implode(',', UserRole::values())],
        ]);

        // Admin users cannot create System Admin accounts
        $requestedRole = UserRole::from($validated['role']);
        if ($currentUser->isAdmin() && $requestedRole === UserRole::SYSTEM_ADMIN) {
            abort(403, 'You do not have permission to create System Admin accounts.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']), // Explicitly using bcrypt
            'role' => $requestedRole,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): Response
    {
        // Only Admin and System Admin can access user management
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access user management.');
        }

        // Admin users cannot view System Admin accounts
        if ($currentUser->isAdmin() && $user->isSystemAdmin()) {
            abort(403, 'You do not have permission to view this user.');
        }

        return Inertia::render('Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value,
                'role_display' => $user->role?->displayName(),
                'email_verified_at' => optional($user->email_verified_at)->toDateTimeString(),
                'created_at' => optional($user->created_at)->toDateTimeString(),
                'updated_at' => optional($user->updated_at)->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        // Only Admin and System Admin can access user management
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access user management.');
        }

        // Admin users cannot edit System Admin accounts
        if ($currentUser->isAdmin() && $user->isSystemAdmin()) {
            abort(403, 'You do not have permission to edit this user.');
        }

        $roles = collect(UserRole::all())
            ->filter(function ($role) use ($currentUser) {
                // Admin users cannot assign System Admin role
                if ($currentUser->isAdmin() && $role === UserRole::SYSTEM_ADMIN) {
                    return false;
                }

                return true;
            })
            ->map(function ($role) {
                return [
                    'value' => $role->value,
                    'label' => $role->displayName(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value,
            ],
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        // Only Admin and System Admin can access user management
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access user management.');
        }

        // Admin users cannot edit System Admin accounts
        if ($currentUser->isAdmin() && $user->isSystemAdmin()) {
            abort(403, 'You do not have permission to edit this user.');
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'string', 'in:'.implode(',', UserRole::values())],
        ];

        // Only validate password if it's provided
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        $validated = $request->validate($rules);

        // Admin users cannot assign System Admin role
        $requestedRole = UserRole::from($validated['role']);
        if ($currentUser->isAdmin() && $requestedRole === UserRole::SYSTEM_ADMIN) {
            abort(403, 'You do not have permission to assign System Admin role.');
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $requestedRole,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($validated['password']); // Explicitly using bcrypt
        }

        $user->update($updateData);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Only Admin and System Admin can access user management
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to access user management.');
        }

        // Admin users cannot delete System Admin accounts
        if ($currentUser->isAdmin() && $user->isSystemAdmin()) {
            abort(403, 'You do not have permission to delete this user.');
        }

        // Admin users cannot delete other Admin accounts
        if ($currentUser->isAdmin() && $user->isAdmin()) {
            abort(403, 'You do not have permission to delete this user.');
        }

        // Prevent deleting your own account
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Show the invitation form.
     */
    public function inviteForm(): Response
    {
        // Only Admin and System Admin can send invitations
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to send invitations.');
        }

        $roles = collect(UserRole::all())
            ->filter(function ($role) use ($currentUser) {
                // Admin users cannot invite System Admin accounts
                if ($currentUser->isAdmin() && $role === UserRole::SYSTEM_ADMIN) {
                    return false;
                }

                return true;
            })
            ->map(function ($role) {
                return [
                    'value' => $role->value,
                    'label' => $role->displayName(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Users/Invite', [
            'roles' => $roles,
        ]);
    }

    /**
     * Send an invitation email to a user.
     */
    public function sendInvitation(Request $request)
    {
        // Only Admin and System Admin can send invitations
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to send invitations.');
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'in:'.implode(',', UserRole::values())],
        ]);

        // Admin users cannot invite System Admin accounts
        if ($currentUser->isAdmin() && $validated['role'] === UserRole::SYSTEM_ADMIN->value) {
            return back()->withErrors(['role' => 'You do not have permission to invite System Admin users.']);
        }

        // Check if there's already a pending invitation for this email
        $existingInvitation = Invitation::where('email', $validated['email'])
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($existingInvitation) {
            return back()->withErrors(['email' => 'An invitation has already been sent to this email address.']);
        }

        // Create the invitation
        $invitation = Invitation::create([
            'email' => $validated['email'],
            'token' => Invitation::generateToken(),
            'role' => $validated['role'],
            'invited_by' => $currentUser->id,
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        // Send the invitation email
        Notification::route('mail', $validated['email'])
            ->notify(new UserInvitation($invitation));

        return redirect()
            ->route('users.index')
            ->with('success', 'Invitation sent successfully to '.$validated['email']);
    }

    /**
     * Display a listing of pending invitations.
     */
    public function invitations(Request $request): Response
    {
        // Only Admin and System Admin can view invitations
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to view invitations.');
        }

        $search = (string) $request->query('search', '');
        $perPage = (int) $request->query('perPage', 10);
        $allowed = [10, 25, 50, 100];
        if (! in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $query = Invitation::with('invitedBy');

        if ($search !== '') {
            $query->where('email', 'like', "%{$search}%");
        }

        $invitations = $query
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($invitation) {
                $role = UserRole::tryFrom($invitation->role);

                return [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role,
                    'role_display' => $role ? $role->displayName() : 'Staff',
                    'used' => $invitation->used,
                    'expires_at' => optional($invitation->expires_at)->toDateTimeString(),
                    'invited_by' => $invitation->invitedBy->name,
                    'is_expired' => $invitation->expires_at->isPast(),
                    'is_valid' => $invitation->isValid(),
                    'created_at' => optional($invitation->created_at)->toDateTimeString(),
                ];
            });

        return Inertia::render('Users/Invitations', [
            'invitations' => $invitations,
            'filters' => [
                'search' => $search,
                'perPage' => $perPage,
            ],
        ]);
    }

    /**
     * Resend an invitation.
     */
    public function resendInvitation(Invitation $invitation)
    {
        // Only Admin and System Admin can resend invitations
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to resend invitations.');
        }

        // Check if invitation was already used
        if ($invitation->used) {
            return back()->withErrors(['error' => 'This invitation has already been used.']);
        }

        // Update expiration date and generate new token
        $invitation->update([
            'token' => Invitation::generateToken(),
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        // Resend the invitation email
        Notification::route('mail', $invitation->email)
            ->notify(new UserInvitation($invitation));

        return back()->with('success', 'Invitation resent successfully.');
    }

    /**
     * Cancel/delete an invitation.
     */
    public function cancelInvitation(Invitation $invitation)
    {
        // Only Admin and System Admin can cancel invitations
        $currentUser = auth()->user();
        if (! $currentUser || ! $currentUser->hasAdminPrivileges()) {
            abort(403, 'You do not have permission to cancel invitations.');
        }

        $invitation->delete();

        return back()->with('success', 'Invitation cancelled successfully.');
    }
}
