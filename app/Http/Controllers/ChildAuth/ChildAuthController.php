<?php

namespace App\Http\Controllers\ChildAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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
    
            $deviceName = $request->device_name ?? ($request->userAgent() ?? 'API Token');
    
            $token = $user->createToken($deviceName)->plainTextToken;
    
            return response()->json([
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch(\Exception $e) {
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
            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Error during logout. Please try again.'], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 403);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }
}
