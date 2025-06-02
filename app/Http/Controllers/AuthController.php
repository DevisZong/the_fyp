<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (\Exception $e) {
            Log::error('Registration failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Authenticate user and return token
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required',
                'device_name' => 'nullable|string',
            ]);

            $user = User::where('username', $request->username)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'username' => ['The provided credentials are incorrect.'],
                ]);
            }
            // Check if the user is an admin
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            $deviceName = $request->device_name ?? ($request->userAgent() ?? 'API Token');

            $token = $user->createToken($deviceName)->plainTextToken;

            // Log activity
            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'role' => $user->getRoleNames()->first() ?? '',
                'user_name' => $user->name,
                'action' => 'login',
                'status' => 'active',
            ]);

            return response()->json([
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (\Exception $e) {
            Log::error('Login failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the authenticated user details
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Logout (revoke token)
     */


    public function logout(Request $request)
    {
        // Ensure we have a valid user before proceeding
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            // Revoke the token that was used to authenticate the current request
            $user->currentAccessToken()->delete();

            // Log activity
            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'role' => $user->getRoleNames()->first() ?? '',
                'user_name' => $user->name,
                'action' => 'logout',
                'status' => 'inactive',
            ]);
            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Error during logout. Please try again.'], 500);
        }
    }

    public function healthcareLogin(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required',
                'device_name' => 'nullable|string',
            ]);

            $user = User::where('username', $request->username)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'username' => ['The provided credentials are incorrect.'],
                ]);
            }
            // Check if the user is a healthcare-provider
            if (!$user->hasRole('doctor|nurse')) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            $deviceName = $request->device_name ?? ($request->userAgent() ?? 'API Token');

            $token = $user->createToken($deviceName)->plainTextToken;

            // Get the healthcare provider id
            $healthCareProvider = \App\Models\HealthCareProvider::where('user_id', $user->id)->first();
            $healthCareProviderId = $healthCareProvider ? $healthCareProvider->id : null;

            // Log activity
            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'role' => $user->getRoleNames()->first() ?? '',
                'user_name' => $user->name,
                'action' => 'login',
                'status' => 'active',
            ]);

            return response()->json([
                'user' => $user,
                'healthcare_provider_id' => $healthCareProviderId,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (\Exception $e) {
            Log::error('Login failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
