<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\VitaminAndDeworming;
use Illuminate\Http\Request;

class VitaminAndDewormingController extends Controller
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'child_id' => 'required|exists:children,id',
                'Vitamin_A' => 'required|boolean',
                'Deworming' => 'required|boolean',
            ]);
            VitaminAndDeworming::create([
                'child_id' => $validated['child_id'],
                'Vitamin_A' => $validated['Vitamin_A'],
                'Deworming' => $validated['Deworming'],
                'status' => true
            ]);
            return response()->json([
                'message' => 'Vitamin and Deworming record created successfully',
                'data' => $validated
            ], 201);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */

    /**
     * this method is for viewing the vitamin and deworming records of the authenticated child
     */
    public function show(Request $request)
    {
        try {
            $user = $request->user();

            // Find the child record associated with the authenticated user
            $child = Child::where('user_id', $user->id)->first();
            if (!$child) {
                return response()->json([
                    'message' => 'Child not found'
                ], 404);
            }

            // Query the VitaminAndDeworming table for all records matching the child_id
            $records = VitaminAndDeworming::where('child_id', $child->id)
                ->get(['created_at', 'Vitamin_A', 'Deworming', 'status']);

            // Return the records in the response
            return response()->json([
                'message' => 'Records fetched successfully',
                'data' => $records
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // This method is for showing the vitamin and deworming records of a child to the parent
    public function showParent(Request $request)
    {
        try{
            $user = $request->user();

            $child = Child::where('user_id', $user->id)->first();
            if(!$child){
                return response()->json([
                    'message' => 'Child not found'
                ], 404);
            }
            $records = VitaminAndDeworming::where('child_id', $child->id)->get(['created_at', 'Vitamin_A', 'Deworming', 'status']);
            return response()->json([
                'message' => 'Records fetched successfully',
                'data' => $records
            ], 200);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error fetching data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VitaminAndDeworming $vitaminAndDeworming)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VitaminAndDeworming $vitaminAndDeworming)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VitaminAndDeworming $vitaminAndDeworming)
    {
        //
    }
}
