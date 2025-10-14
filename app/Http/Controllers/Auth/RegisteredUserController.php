<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Invitation;
use App\UserRole;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(Request $request): Response
    {
        $token = $request->query('token');
        $invitation = null;

        if ($token) {
            $invitation = Invitation::where('token', $token)->first();
        }

        return Inertia::render('auth/Register', [
            'token' => $token,
            'invitationEmail' => $invitation?->email,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Get invitation from request (set by ValidateInvitation middleware)
        $invitation = $request->input('invitation');

        if (!$invitation instanceof Invitation) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Invalid invitation.']);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Validate that the email matches the invitation
        if (strtolower($request->email) !== strtolower($invitation->email)) {
            return back()->withErrors(['email' => 'Email must match the invitation email.']);
        }

        // Create user with the role specified in the invitation
        $invitedRole = UserRole::tryFrom($invitation->role) ?? UserRole::STAFF;
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $invitedRole,
        ]);

        // Mark invitation as used
        $invitation->markAsUsed();

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
