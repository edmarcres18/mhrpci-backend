<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string'], // Keep nullable for initial validation
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $credentials = $validator->validated();

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        // Conditional check for device_name based on biometric registration
        if ($user->biometrics()->exists() && empty($credentials['device_name'])) {
            return response()->json([
                'success' => false,
                'errors' => ['device_name' => ['The device name is required when biometrics are registered for this user.']],
            ], 422);
        }

        $deviceName = $credentials['device_name'] ?? ($user->name . ' - Device'); // Provide a default if not given

        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json(['success' => true, 'token' => $token, 'user' => $user]);
    }

    /**
     * Log the user out (revoke the current token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
    }
}
