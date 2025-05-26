<?php

namespace App\Http\Controllers;

use App\Models\HealthCareProvider;
use Illuminate\Http\Request;

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
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'license' => 'required|string|unique:health_care_providers,license|max:255',
                'userRole' => 'required|string|max:50',
                'facility' => 'required|string|max:255',
                'contact' => 'required|string|max:15',
                'gender' => 'required|string|max:10',
                'status' => 'required|boolean',
            ]);
            
            $healthCareProvider = HealthCareProvider::create($validated);
            // Optionally, you can attach the user role if needed

            $plainPassword = $validated['facility'] . '@' . $validated['license'];
            
            $user = $healthCareProvider->user()->create([
                'name' => $validated['name'],
                'license' => $validated['license'],
                'password' => bcrypt($plainPassword),
                'role' => $validated['userRole'],
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;
            $healthCareProvider->user_id = $user->id;
            $healthCareProvider->save();

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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HealthCareProvider $healthCareProvider)
    {
        //
    }
}
