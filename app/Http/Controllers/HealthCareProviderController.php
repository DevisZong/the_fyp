<?php

namespace App\Http\Controllers;

use App\Models\HealthCareProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthCareProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'license' => 'required|string|unique:health_care_providers,license|max:255',
                'userRole' => 'required|string|max:50',
                'facility' => 'required|string|max:255',
                'contact' => 'required|string|max:15',
                'gender' => 'required|string|max:10',
                'status' => 'required|boolean',
                'picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            // Only allow nurse or doctor as userRole
            if (!in_array(strtolower($validated['userRole']), ['nurse', 'doctor'])) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid userRole. Only nurse or doctor are allowed.'
                ], 422);
            }

            $plainPassword = $validated['facility'] . '@' . $validated['license'];

            // Create the user first
            $user = \App\Models\User::create([
                'name' => $validated['name'],
                'username' => $validated['license'],
                'password' => bcrypt($plainPassword),
            ]);
            // Assign the role to the user
            $user->assignRole($validated['userRole']);

            // Add user_id to the validated data
            $validated['user_id'] = $user->id;

            // Handle picture upload if provided
            if ($request->hasFile('picture')) {
                $picture = $request->file('picture');
                $pictureName = time() . '_' . $validated['license'] . '.' . $picture->getClientOriginalExtension();
                $picture->move(public_path('uploads/healthcare_providers'), $pictureName);
                $validated['picture'] = 'uploads/healthcare_providers/' . $pictureName;
            }

            // Now create the HealthCareProvider
            $healthCareProvider = HealthCareProvider::create($validated);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Health Care Provider created successfully',
                'data' => $healthCareProvider,
                'login_credentials' => [
                    'license' => $validated['license'],
                    'password' => $plainPassword,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Health Care Provider',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(HealthCareProvider $healthCareProvider)
    {
        // Authenticate using the token (handled by middleware, e.g., sanctum:auth)
        // Load related user and children if needed
        $healthCareProvider->load(['user', 'children', 'growthRecords', 'vaccinations']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $healthCareProvider->id,
                'name' => $healthCareProvider->name,
                'license' => $healthCareProvider->license,
                'userRole' => $healthCareProvider->userRole,
                'facility' => $healthCareProvider->facility,
                'contact' => $healthCareProvider->contact,
                'gender' => $healthCareProvider->gender,
                'status' => $healthCareProvider->status,
                'picture' => $healthCareProvider->picture ? url($healthCareProvider->picture) : null,
                'user' => $healthCareProvider->user,
                'children' => $healthCareProvider->children,
                'growth_records' => $healthCareProvider->growthRecords,
                'vaccinations' => $healthCareProvider->vaccinations,
                'created_at' => $healthCareProvider->created_at,
                'updated_at' => $healthCareProvider->updated_at,
            ]
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HealthCareProvider $healthCareProvider)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HealthCareProvider $healthCareProvider)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'license' => 'sometimes|string|max:255|unique:health_care_providers,license,' . $healthCareProvider->id,
                'userRole' => 'sometimes|string|max:50',
                'facility' => 'sometimes|string|max:255',
                'contact' => 'sometimes|string|max:15',
                'gender' => 'sometimes|string|max:10',
                'status' => 'sometimes|boolean',
                'picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            // Handle picture upload if provided
            if ($request->hasFile('picture')) {
                // Delete old picture if exists
                if ($healthCareProvider->picture && file_exists(public_path($healthCareProvider->picture))) {
                    unlink(public_path($healthCareProvider->picture));
                }

                $picture = $request->file('picture');
                $pictureName = time() . '_' . $healthCareProvider->license . '.' . $picture->getClientOriginalExtension();
                $picture->move(public_path('uploads/healthcare_providers'), $pictureName);
                $validated['picture'] = 'uploads/healthcare_providers/' . $pictureName;
            }

            // Update user if name or license changed
            if (isset($validated['name']) || isset($validated['license'])) {
                $user = $healthCareProvider->user;
                $user->name = $validated['name'] ?? $user->name;
                $user->username = $validated['license'] ?? $user->username;
                $user->save();
            }

            // Update role if userRole changed
            if (isset($validated['userRole'])) {
                if (!in_array(strtolower($validated['userRole']), ['nurse', 'doctor'])) {
                    throw new \Exception('Invalid userRole. Only nurse or doctor are allowed.');
                }
                $user = $healthCareProvider->user;
                $user->syncRoles([$validated['userRole']]);
            }

            $healthCareProvider->update($validated);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Health Care Provider updated successfully',
                'data' => $healthCareProvider->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Health Care Provider',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HealthCareProvider $healthCareProvider)
    {
        //
    }

    /**
     * Show the authenticated healthcare provider's details using the auth token.
     */
    public function showAuthenticated(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }
        // Find the healthcare provider by user_id
        $healthCareProvider = HealthCareProvider::where('user_id', $user->id)
            ->with(['user', 'children', 'growthRecords'])
            ->first();
        if (!$healthCareProvider) {
            return response()->json(['status' => 'error', 'message' => 'Healthcare provider not found'], 404);
        }
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $healthCareProvider->id,
                'name' => $healthCareProvider->name,
                'license' => $healthCareProvider->license,
                'userRole' => $healthCareProvider->userRole,
                'facility' => $healthCareProvider->facility,
                'contact' => $healthCareProvider->contact,
                'gender' => $healthCareProvider->gender,
                'status' => $healthCareProvider->status,
                'picture' => $healthCareProvider->picture ? url($healthCareProvider->picture) : null,
                'user' => $healthCareProvider->user,
                'children' => $healthCareProvider->children,
                'children_count' => $healthCareProvider->children->count(),
                'growth_records' => $healthCareProvider->growthRecords,
                'vaccinations' => $healthCareProvider->vaccinations,
                'created_at' => $healthCareProvider->created_at,
                'updated_at' => $healthCareProvider->updated_at,
            ]
        ], 200);
    }
}
