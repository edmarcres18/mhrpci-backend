<?php

namespace App\Http\Controllers;

use App\Models\UserBiometric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BiometricAuthController extends Controller
{
    /**
     * Register a biometric identifier for the authenticated user.
     */
    public function registerBiometric(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => ['required', 'string', 'unique:user_biometrics,device_id'],
            'biometric_key' => ['nullable', 'string'], // A representation of the biometric, e.g., a public key
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $user = $request->user(); // Authenticated user
        $validated = $validator->validated();

        $user->biometrics()->create([
            'device_id' => $validated['device_id'],
            'biometric_key' => $validated['biometric_key'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Biometric registered successfully.']);
    }

    /**
     * Authenticate a user using a registered biometric identifier.
     */
    public function biometricLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => ['required', 'string'],
            'biometric_key' => ['nullable', 'string'], // A representation of the biometric, e.g., a public key for verification
            'device_name' => ['required', 'string'], // For Sanctum token
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $validated = $validator->validated();

        $userBiometric = UserBiometric::where('device_id', $validated['device_id'])->first();

        if (! $userBiometric || ($validated['biometric_key'] && $userBiometric->biometric_key !== $validated['biometric_key'])) {
            return response()->json(['success' => false, 'message' => 'Invalid biometric credentials.'], 401);
        }

        $user = $userBiometric->user;

        // At this point, the Flutter app would have locally verified the biometric.
        // We are trusting the `device_id` and `biometric_key` provided by the client,
        // assuming the client has done its local biometric verification.
        // For enhanced security, a challenge-response mechanism involving cryptographic signing
        // with a private key stored in the secure enclave of the device would be ideal.
        // For this example, we'll assume `biometric_key` acts as a shared secret or public key for verification.

        $token = $user->createToken($validated['device_name'])->plainTextToken;

        return response()->json(['success' => true, 'token' => $token, 'user' => $user]);
    }
}
