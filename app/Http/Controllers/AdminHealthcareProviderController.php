<?php

namespace App\Http\Controllers;

use App\Models\HealthCareProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminHealthcareProviderController extends Controller
{
    /**
     * Display a listing of healthcare providers for admin
     */
    public function index()
    {
        try {
            $providers = HealthCareProvider::with('user')->get();

            // Transform data to match frontend expectations
            $transformedData = $providers->map(function ($provider) {
                return [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'license' => $provider->license,
                    'role' => ucfirst($provider->userRole), // Frontend expects 'role' not 'userRole'
                    'facility' => $provider->facility,
                    'contact' => $provider->contact,
                    'gender' => ucfirst($provider->gender),
                    'picture' => $provider->picture ? url($provider->picture) : null,
                    'status' => $provider->status,
                    'created_at' => $provider->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $provider->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Healthcare providers retrieved successfully',
                'data' => $transformedData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve healthcare providers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created healthcare provider
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'Name' => 'required|string|max:255',
                'License' => 'required|string|unique:health_care_providers,license|max:255',
                'userRole' => 'required|string|in:doctor,nurse',
                'Facility' => 'required|string|max:255',
                'Contact' => 'required|string|max:15',
                'gender' => 'required|string|in:male,female,other',
                'Picture' => 'nullable|string' // Base64 or file path
            ]);

            // Create password from facility and license
            $plainPassword = $validated['Facility'] . '@' . $validated['License'];

            // Create user first
            $user = User::create([
                'name' => $validated['Name'],
                'username' => $validated['License'],
                'password' => Hash::make($plainPassword),
            ]);

            // Assign role to user
            $user->assignRole($validated['userRole']);

            // Prepare healthcare provider data
            $providerData = [
                'name' => $validated['Name'],
                'license' => $validated['License'],
                'userRole' => $validated['userRole'],
                'facility' => $validated['Facility'],
                'contact' => $validated['Contact'],
                'gender' => strtolower($validated['gender']),
                'status' => true,
                'user_id' => $user->id,
            ];

            // Handle picture upload if provided
            if (isset($validated['Picture']) && !empty($validated['Picture'])) {
                // If it's a base64 string, save it as a file
                if (strpos($validated['Picture'], 'data:image') === 0) {
                    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $validated['Picture']));
                    $imageName = time() . '_' . $validated['License'] . '.jpg';
                    $imagePath = 'uploads/healthcare_providers/' . $imageName;

                    // Create directory if it doesn't exist
                    if (!file_exists(public_path('uploads/healthcare_providers'))) {
                        mkdir(public_path('uploads/healthcare_providers'), 0755, true);
                    }

                    file_put_contents(public_path($imagePath), $imageData);
                    $providerData['picture'] = $imagePath;
                } else {
                    $providerData['picture'] = $validated['Picture'];
                }
            }

            // Create healthcare provider
            $provider = HealthCareProvider::create($providerData);

            DB::commit();

            // Return data in format expected by frontend
            $responseData = [
                'id' => $provider->id,
                'name' => $provider->name,
                'license' => $provider->license,
                'role' => ucfirst($provider->userRole),
                'facility' => $provider->facility,
                'contact' => $provider->contact,
                'gender' => ucfirst($provider->gender),
                'picture' => $provider->picture ? url($provider->picture) : null,
                'status' => $provider->status,
                'created_at' => $provider->created_at->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Healthcare provider created successfully',
                'data' => $responseData,
                'login_credentials' => [
                    'username' => $validated['License'],
                    'password' => $plainPassword,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create healthcare provider',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified healthcare provider
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $provider = HealthCareProvider::with('user')->findOrFail($id);

            $validated = $request->validate([
                'Name' => 'sometimes|string|max:255',
                'License' => [
                    'sometimes',
                    'string',
                    'max:255',
                    Rule::unique('health_care_providers', 'license')->ignore($provider->id)
                ],
                'userRole' => 'sometimes|string|in:doctor,nurse',
                'Facility' => 'sometimes|string|max:255',
                'Contact' => 'sometimes|string|max:15',
                'gender' => 'sometimes|string|in:male,female,other',
                'Picture' => 'nullable|string'
            ]);

            // Update user if name or license changed
            if (isset($validated['Name']) || isset($validated['License'])) {
                $user = $provider->user;
                if (isset($validated['Name'])) {
                    $user->name = $validated['Name'];
                }
                if (isset($validated['License'])) {
                    $user->username = $validated['License'];
                }
                $user->save();
            }

            // Update role if changed
            if (isset($validated['userRole'])) {
                $provider->user->syncRoles([$validated['userRole']]);
            }

            // Prepare update data
            $updateData = [];
            if (isset($validated['Name'])) $updateData['name'] = $validated['Name'];
            if (isset($validated['License'])) $updateData['license'] = $validated['License'];
            if (isset($validated['userRole'])) $updateData['userRole'] = $validated['userRole'];
            if (isset($validated['Facility'])) $updateData['facility'] = $validated['Facility'];
            if (isset($validated['Contact'])) $updateData['contact'] = $validated['Contact'];
            if (isset($validated['gender'])) $updateData['gender'] = strtolower($validated['gender']);

            // Handle picture update
            if (isset($validated['Picture'])) {
                if (empty($validated['Picture'])) {
                    // Remove picture
                    if ($provider->picture && file_exists(public_path($provider->picture))) {
                        unlink(public_path($provider->picture));
                    }
                    $updateData['picture'] = null;
                } else {
                    // Update picture
                    if (strpos($validated['Picture'], 'data:image') === 0) {
                        // Delete old picture
                        if ($provider->picture && file_exists(public_path($provider->picture))) {
                            unlink(public_path($provider->picture));
                        }

                        // Save new picture
                        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $validated['Picture']));
                        $imageName = time() . '_' . $provider->license . '.jpg';
                        $imagePath = 'uploads/healthcare_providers/' . $imageName;

                        if (!file_exists(public_path('uploads/healthcare_providers'))) {
                            mkdir(public_path('uploads/healthcare_providers'), 0755, true);
                        }

                        file_put_contents(public_path($imagePath), $imageData);
                        $updateData['picture'] = $imagePath;
                    }
                }
            }

            // Update provider
            $provider->update($updateData);

            DB::commit();

            // Return updated data
            $provider->refresh();
            $responseData = [
                'id' => $provider->id,
                'name' => $provider->name,
                'license' => $provider->license,
                'role' => ucfirst($provider->userRole),
                'facility' => $provider->facility,
                'contact' => $provider->contact,
                'gender' => ucfirst($provider->gender),
                'picture' => $provider->picture ? url($provider->picture) : null,
                'status' => $provider->status,
                'updated_at' => $provider->updated_at->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Healthcare provider updated successfully',
                'data' => $responseData
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update healthcare provider',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete the specified healthcare provider
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $provider = HealthCareProvider::with('user')->findOrFail($id);

            // Delete picture if exists
            if ($provider->picture && file_exists(public_path($provider->picture))) {
                unlink(public_path($provider->picture));
            }

            // Delete associated user
            if ($provider->user) {
                $provider->user->delete();
            }

            // Delete provider
            $provider->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Healthcare provider deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete healthcare provider',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password for a healthcare provider
     */
    public function resetPassword($id)
    {
        try {
            $provider = HealthCareProvider::with('user')->findOrFail($id);

            // Generate new password
            $newPassword = $provider->facility . '@' . $provider->license;

            // Update user password
            $provider->user->update([
                'password' => Hash::make($newPassword)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password reset successfully',
                'new_password' => $newPassword
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
