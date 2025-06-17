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
     * Admin: Get all users in the system
     */
    public function getAllUsers(Request $request)
    {
        try {
            $users = User::with(['roles', 'child', 'healthCareProvider'])->get()->map(function ($user) {
                $userData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'roles' => $user->getRoleNames(),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];

                // Add specific details based on role
                if ($user->hasRole('child') && $user->child) {
                    $userData['child_details'] = [
                        'child_name' => $user->child->childName,
                        'child_no' => $user->child->childNo,
                        'father_name' => $user->child->fatherName,
                        'date_of_birth' => $user->child->dateOfBirth,
                        'gender' => $user->child->gender,
                    ];
                }

                if (($user->hasRole('doctor') || $user->hasRole('nurse')) && $user->healthCareProvider) {
                    $userData['healthcare_details'] = [
                        'license' => $user->healthCareProvider->license,
                        'facility' => $user->healthCareProvider->facility,
                        'contact' => $user->healthCareProvider->contact,
                        'gender' => $user->healthCareProvider->gender,
                        'status' => $user->healthCareProvider->status,
                    ];
                }

                return $userData;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Users fetched successfully',
                'data' => $users,
                'total' => $users->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Get all users failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Reset user password to default
     */
    public function resetUserPassword(Request $request)
    {
        try {
            $request->validate([
                'identifier' => 'required|string',
                'identifier_type' => 'required|string|in:user_id,email,child_no,license',
            ]);

            $identifier = $request->identifier;
            $identifierType = $request->identifier_type;

            // Find user based on identifier type
            $query = User::with(['roles', 'child', 'healthCareProvider']);

            switch ($identifierType) {
                case 'user_id':
                    $targetUser = $query->where('id', $identifier)->first();
                    break;
                case 'email':
                    $targetUser = $query->where('email', $identifier)->first();
                    break;
                case 'child_no':
                    $targetUser = $query->whereHas('child', function ($q) use ($identifier) {
                        $q->where('childNo', $identifier);
                    })->first();
                    break;
                case 'license':
                    $targetUser = $query->whereHas('healthCareProvider', function ($q) use ($identifier) {
                        $q->where('license', $identifier);
                    })->first();
                    break;
                default:
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid identifier type',
                    ], 400);
            }

            if (!$targetUser) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found with the provided identifier',
                ], 404);
            }
            $defaultPassword = $this->getDefaultPassword($targetUser);

            if (!$defaultPassword) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot determine default password for this user type',
                ], 422);
            }

            // Update the password
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
                'description' => "Reset password for user: {$targetUser->name} (ID: {$targetUser->id})",
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User password has been reset to default successfully',
                'user' => [
                    'id' => $targetUser->id,
                    'name' => $targetUser->name,
                    'email' => $targetUser->email,
                    'username' => $targetUser->username,
                    'roles' => $targetUser->getRoleNames(),
                ],
                'reset_info' => [
                    'identifier_used' => $identifierType,
                    'identifier_value' => $identifier,
                    'default_password_hint' => $this->getPasswordHint($targetUser),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Reset user password failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reset user password',
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
     * Get password hint for user (for admin reference)
     */
    private function getPasswordHint($user)
    {
        if ($user->hasRole('admin')) {
            return 'Default admin password (Admin123)';
        }

        if ($user->hasRole('child') && $user->child) {
            return "Father's name: " . $user->child->fatherName;
        }

        if (($user->hasRole('doctor') || $user->hasRole('nurse')) && $user->healthCareProvider) {
            return "Format: {facility}@{license}";
        }

        return 'Contact system administrator';
    }

    /**
     * Admin: Search users by various criteria
     */
    public function searchUsers(Request $request)
    {
        try {
            $request->validate([
                'search' => 'required|string|min:2',
                'type' => 'nullable|string|in:all,admin,child,healthcare',
                'limit' => 'nullable|integer|max:50',
            ]);

            $search = $request->search;
            $type = $request->type ?? 'all';
            $limit = $request->limit ?? 20;

            $query = User::with(['roles', 'child', 'healthCareProvider']);

            // Filter by user type/role
            if ($type !== 'all') {
                switch ($type) {
                    case 'admin':
                        $query->whereHas('roles', fn($q) => $q->where('name', 'admin'));
                        break;
                    case 'child':
                        $query->whereHas('roles', fn($q) => $q->where('name', 'child'));
                        break;
                    case 'healthcare':
                        $query->whereHas('roles', fn($q) => $q->whereIn('name', ['doctor', 'nurse']));
                        break;
                }
            }

            // Search in multiple fields
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhereHas('child', function ($childQuery) use ($search) {
                        $childQuery->where('childName', 'LIKE', "%{$search}%")
                            ->orWhere('childNo', 'LIKE', "%{$search}%")
                            ->orWhere('fatherName', 'LIKE', "%{$search}%")
                            ->orWhere('motherName', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('healthCareProvider', function ($hcpQuery) use ($search) {
                        $hcpQuery->where('license', 'LIKE', "%{$search}%")
                            ->orWhere('facility', 'LIKE', "%{$search}%");
                    });
            });

            $users = $query->limit($limit)->get()->map(function ($user) {
                $userData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                    'created_at' => $user->created_at,
                ];

                // Add specific details based on role
                if ($user->hasRole('child') && $user->child) {
                    $userData['child_details'] = [
                        'child_name' => $user->child->childName,
                        'child_no' => $user->child->childNo,
                        'father_name' => $user->child->fatherName,
                        'mother_name' => $user->child->motherName,
                        'date_of_birth' => $user->child->dateOfBirth,
                        'gender' => $user->child->gender,
                    ];
                    $userData['identifier_options'] = [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'child_no' => $user->child->childNo,
                    ];
                }

                if (($user->hasRole('doctor') || $user->hasRole('nurse')) && $user->healthCareProvider) {
                    $userData['healthcare_details'] = [
                        'license' => $user->healthCareProvider->license,
                        'facility' => $user->healthCareProvider->facility,
                        'contact' => $user->healthCareProvider->contact,
                        'gender' => $user->healthCareProvider->gender,
                        'status' => $user->healthCareProvider->status,
                    ];
                    $userData['identifier_options'] = [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'license' => $user->healthCareProvider->license,
                    ];
                }

                if ($user->hasRole('admin')) {
                    $userData['identifier_options'] = [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ];
                }

                return $userData;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Search results fetched successfully',
                'data' => $users,
                'total' => $users->count(),
                'search_query' => $search,
                'search_type' => $type,
            ]);
        } catch (\Exception $e) {
            Log::error('Search users failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to search users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
