<?php

namespace App\Http\Controllers;

use App\Models\GrowthRecords;
use Illuminate\Http\Request;
use App\Models\Child;

class GrowthRecordsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json([
                'status' => 'success',
                'message' => 'Growth Data fetched Successfully',
                'data' => GrowthRecords::all(),
            ]);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error fetching growth data',
                'message' => 'Failed to fetch growth data',
                'error' => $e->getMessage(),
            ]);
            
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    // app/Http/Controllers/ChildController.php


    /**
 * Get the growth chart for a child.
 */
/**
 * Get the growth chart for a child.
 */
    public function getGrowthChart(Request $request)
    {
        try {
            $validated = $request->validate([
                'child_id' => 'required|integer|exists:children,id',
            ]);

            $child_id = $validated['child_id'];
        
            $growthRecords = GrowthRecords::where('child_id', $child_id)
                ->get()
                ->groupBy(function ($record) {
                    return \Carbon\Carbon::parse($record->created_at)->format('Y-m'); // Group by month (YYYY-MM)
                })
                ->map(function ($records, $month) {
                    return [
                        'month' => $month,
                        'weights' => $records->pluck('weight'), // Collect all weights for the month
                    ];
                })
                ->values();

            if ($growthRecords->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No growth records found for the specified child.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Growth chart fetched successfully.',
                'data' => $growthRecords,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching growth chart.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    


    /**
     * Get the growth chart for the authenticated child user.
     */
    public function getGrowthChartByToken(Request $request)
    {
        try {
            $user = $request->user();

            // Ensure the user is authenticated and has the 'child' role
            if (!$user || $user->role !== 'child') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized or not a child user.',
                ], 403);
            }

            // Find the child record associated with this user
            $child = Child::where('user_id', $user->id)->first();

            if (!$child) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No child record found for this user.',
                ], 404);
            }

            $growthRecords = GrowthRecords::where('child_id', $child->id)
                ->get()
                ->groupBy(function ($record) {
                    return \Carbon\Carbon::parse($record->created_at)->format('Y-m'); // Group by month (YYYY-MM)
                })
                ->map(function ($records, $month) {
                    return [
                        'month' => $month,
                        'weights' => $records->pluck('weight'),
                    ];
                })
                ->values();

            if ($growthRecords->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No growth records found for the authenticated child.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Growth chart fetched successfully.',
                'data' => $growthRecords,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching growth chart.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

// ...existing code...

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $validated = $request->validate([
                'child_id' => 'required|string|max:255',
                'health_care_provider_id' => 'required|string|max:255',
                'weight' => 'required|numeric',
                'height' => 'required|numeric',
            ]);

            $growthRecord = GrowthRecords::create($validated);
            return response()->json([
                'status' => 'success',
                'message' => 'Data stored successfully',
                'data' => $growthRecord
            ]);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Error storing data',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(GrowthRecords $growthRecords)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GrowthRecords $growthRecords)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GrowthRecords $growthRecords)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GrowthRecords $growthRecords)
    {
        //
    }
}
