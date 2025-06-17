<?php

namespace App\Http\Controllers\ChildAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\Child;

class ChildAuthController extends Controller
{
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

            // Check if the user is a child (using Spatie's hasRole method)
            if (! $user->hasRole('child')) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            // Check if this is the user's first login by checking activity logs
            $hasLoggedInBefore = \App\Models\ActivityLog::where('user_id', $user->id)
                ->where('action', 'login')
                ->exists();

            $isFirstLogin = !$hasLoggedInBefore;

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
                'is_first_login' => $isFirstLogin,
                'redirect_to_change_password' => $isFirstLogin
            ]);
        } catch (\Exception $e) {
            Log::error('Login failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

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

    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:8|confirmed',
            ]);

            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect'], 403);
            }

            // Check if new password is same as current password
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'message' => 'New password cannot be the same as your current password'
                ], 422);
            }

            DB::beginTransaction();
            try {
                $user->password = Hash::make($request->new_password);
                $user->save();

                // Log activity
                \App\Models\ActivityLog::create([
                    'user_id' => $user->id,
                    'role' => $user->getRoleNames()->first() ?? '',
                    'user_name' => $user->name,
                    'action' => 'password_changed',
                    'status' => 'active',
                ]);

                DB::commit();
                return response()->json(['message' => 'Password changed successfully']);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Password change failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to change password',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
