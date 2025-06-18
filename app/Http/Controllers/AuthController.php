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
                // Log failed login attempt
                \App\Models\ActivityLog::create([
                    'user_id' => $user ? $user->id : null,
                    'role' => 'unknown',
                    'user_name' => $request->username,
                    'action' => 'Failed Admin Login',
                    'details' => 'Invalid credentials attempt for admin login',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'status' => 'error',
                    'type' => 'login',
                ]);

                throw ValidationException::withMessages([
                    'username' => ['The provided credentials are incorrect.'],
                ]);
            }
            // Check if the user is an admin
            if (!$user->hasRole('admin')) {
                // Log unauthorized access attempt
                \App\Models\ActivityLog::create([
                    'user_id' => $user->id,
                    'role' => $user->getRoleNames()->first() ?? 'user',
                    'user_name' => $user->name,
                    'action' => 'Unauthorized Admin Access',
                    'details' => 'Non-admin user attempted to access admin panel',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'status' => 'error',
                    'type' => 'login',
                ]);

                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            $deviceName = $request->device_name ?? ($request->userAgent() ?? 'API Token');

            $token = $user->createToken($deviceName)->plainTextToken;

            // Log activity
            SystemLogsController::logActivity(
                $user,
                'Admin Login',
                'Successful admin login to dashboard',
                'success',
                'login',
                $request
            );

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
            SystemLogsController::logActivity(
                $user,
                'Logout',
                'User logged out successfully',
                'success',
                'logout',
                $request
            );
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
            SystemLogsController::logActivity(
                $user,
                'Healthcare Login',
                'Healthcare provider login - ' . $user->getRoleNames()->first(),
                'success',
                'login',
                $request
            );

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

    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The provided current password is incorrect.'],
                ]);
            }

            // Check if new password is same as current password
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'New password cannot be the same as your current password.',
                ], 422);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json(['message' => 'Password changed successfully']);
        } catch (\Exception $e) {
            Log::error('Change password failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Change password failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Get all child users in the system
     */
    public function getAllUsers(Request $request)
    {
        try {
            // Only get users with child role and their child details
            $users = User::with(['roles', 'child'])
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'child');
                })
                ->whereHas('child') // Ensure they have child record
                ->get()
                ->map(function ($user) {
                    $child = $user->child;
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'child_name' => $child->childName,
                        'child_no' => $child->childNo,
                        'father_name' => $child->fatherName,
                        'mother_name' => $child->motherName,
                        'date_of_birth' => $child->date_of_birth,
                        'gender' => $child->gender,
                        'phone_no' => $child->phoneNo,
                        'address' => $child->address,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                        'status' => 'Active', // You can customize this based on your needs
                    ];
                });

            return response()->json([
                'status' => 'success',
                'message' => 'Child users fetched successfully',
                'data' => $users,
                'total' => $users->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch users: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Reset child user password to default (father's name)
     */
    public function resetUserPassword(Request $request)
    {
        try {
            $request->validate([
                'identifier_value' => 'required|string',
                'identifier_used' => 'required|string|in:child_no',
            ]);

            $childNo = $request->identifier_value;

            // Find user with child role by child number
            $targetUser = User::with(['child'])
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'child');
                })
                ->whereHas('child', function ($q) use ($childNo) {
                    $q->where('childNo', $childNo);
                })
                ->first();

            if (!$targetUser || !$targetUser->child) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Child not found with the provided child number',
                ], 404);
            }

            $child = $targetUser->child;

            // Reset password to father's name (same as during child creation)
            $defaultPassword = $child->fatherName;
            $targetUser->password = Hash::make($defaultPassword);
            $targetUser->save();

            // Revoke all existing tokens for security
            $targetUser->tokens()->delete();

            // Log the activity
            \App\Models\ActivityLog::create([
                'user_id' => $request->user()->id,
                'role' => $request->user()->getRoleNames()->first() ?? '',
                'user_name' => $request->user()->name,
                'action' => 'password_reset',
                'status' => 'completed',
                'description' => "Reset password for child: {$child->childName} (Child No: {$child->childNo})",
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Child password has been reset successfully',
                'data' => [
                    'child_name' => $child->childName,
                    'child_no' => $child->childNo,
                    'username' => $targetUser->username,
                    'password_hint' => "Password reset to father's name: {$child->fatherName}",
                    'login_instruction' => 'Use child number as username and father\'s name as password'
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Reset child password failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reset child password',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper method to determine default password based on user type
     */
    private function getDefaultPassword($user)
    {
        try {
            if ($user->hasRole('admin')) {
                return 'Admin123';
            }

            if ($user->hasRole('child') && $user->child) {
                return $user->child->fatherName;
            }

            if (($user->hasRole('doctor') || $user->hasRole('nurse')) && $user->healthCareProvider) {
                return $user->healthCareProvider->facility . '@' . $user->healthCareProvider->license;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Get default password failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Admin: Search child users by various criteria
     */
    public function searchUsers(Request $request)
    {
        try {
            $request->validate([
                'search' => 'required|string|min:2',
                'limit' => 'nullable|integer|max:50',
            ]);

            $search = $request->search;
            $limit = $request->limit ?? 20;

            // Only search among child users
            $query = User::with(['child'])
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'child');
                })
                ->whereHas('child');

            // Search in multiple fields
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%")
                    ->orWhereHas('child', function ($childQuery) use ($search) {
                        $childQuery->where('childName', 'LIKE', "%{$search}%")
                            ->orWhere('childNo', 'LIKE', "%{$search}%")
                            ->orWhere('fatherName', 'LIKE', "%{$search}%")
                            ->orWhere('motherName', 'LIKE', "%{$search}%");
                    });
            });

            $users = $query->limit($limit)->get()->map(function ($user) {
                $child = $user->child;
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'child_name' => $child->childName,
                    'child_no' => $child->childNo,
                    'father_name' => $child->fatherName,
                    'mother_name' => $child->motherName,
                    'date_of_birth' => $child->date_of_birth,
                    'gender' => $child->gender,
                    'phone_no' => $child->phoneNo,
                    'address' => $child->address,
                    'created_at' => $user->created_at,
                    'status' => 'Active',
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Search completed successfully',
                'data' => $users,
                'total' => $users->count(),
                'search_term' => $search,
            ]);
        } catch (\Exception $e) {
            Log::error('Search users failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
