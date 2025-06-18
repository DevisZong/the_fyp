<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\VitaminAndDeworming;
use Carbon\Carbon;
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
    public function store(Request $request, $childId)
    {
        try {
            $validated = $request->validate([
                'Vitamin_A' => 'required|boolean',
                'Deworming' => 'required|boolean',
            ]);

            // Check if the child exists
            $child = Child::find($childId);
            if (!$child) {
                return response()->json([
                    'message' => 'Child not found'
                ], 404);
            }

            // Check if child is over 5 years old
            $dob = Carbon::parse($child->date_of_birth);
            $currentAgeInMonths = $dob->diffInMonths(Carbon::now());
            if ($currentAgeInMonths > 60) {
                return response()->json([
                    'message' => 'Child is over 5 years old'
                ], 422);
            }

            // Determine which visit period the child is currently eligible for
            $currentVisitPeriod = null;
            $visitSchedule = [6, 12, 18, 24, 30, 36, 42, 48, 54, 60]; // months
            
            foreach ($visitSchedule as $visitMonth) {
                // Check if child is within 1 month range of the scheduled visit
                if ($currentAgeInMonths >= ($visitMonth - 1) && $currentAgeInMonths <= ($visitMonth + 1)) {
                    $currentVisitPeriod = $visitMonth;
                    break;
                }
            }

            // If child is not within any valid visit period
            if (!$currentVisitPeriod) {
                return response()->json([
                    'message' => "Child is not eligible for vitamin and deworming at current age ({$currentAgeInMonths} months). Next eligible period is at " . 
                                 collect($visitSchedule)->first(function($month) use ($currentAgeInMonths) {
                                     return $month > $currentAgeInMonths;
                                 }) . " months."
                ], 422);
            }

            // Check if a record already exists for this visit period
            $existingRecord = VitaminAndDeworming::where('child_id', $childId)
                ->whereRaw('ABS(TIMESTAMPDIFF(MONTH, ?, created_at)) <= 1', [$dob->copy()->addMonths($currentVisitPeriod)])
                ->where('status', '!=', 'inasubiri')
                ->first();

            if ($existingRecord) {
                $visitNumber = array_search($currentVisitPeriod, $visitSchedule) + 1;
                return response()->json([
                    'message' => "A vitamin and deworming record for visit {$visitNumber} ({$currentVisitPeriod} months period) already exists for this child. Status: {$existingRecord->status}",
                    'existing_record' => [
                        'visit_period' => $currentVisitPeriod . ' months',
                        'visit_number' => $visitNumber,
                        'recorded_date' => $existingRecord->created_at->format('Y-m-d'),
                        'vitamin_a' => $existingRecord->Vitamin_A,
                        'deworming' => $existingRecord->Deworming,
                        'status' => $existingRecord->status
                    ]
                ], 409); // 409 Conflict
            }

            // Find the existing 'inasubiri' record for this period and update it
            $recordToUpdate = VitaminAndDeworming::where('child_id', $childId)
                ->whereRaw('ABS(TIMESTAMPDIFF(MONTH, ?, created_at)) <= 1', [$dob->copy()->addMonths($currentVisitPeriod)])
                ->where('status', 'inasubiri')
                ->first();

            if ($recordToUpdate) {
                // Update the existing record
                $recordToUpdate->update([
                    'Vitamin_A' => $validated['Vitamin_A'],
                    'Deworming' => $validated['Deworming'],
                    'status' => 'imekamilika',
                    'updated_at' => Carbon::now()
                ]);
                $record = $recordToUpdate;
            } else {
                // Create new record if no existing record found (shouldn't happen if system is working correctly)
                $record = VitaminAndDeworming::create([
                    'child_id' => $childId,
                    'Vitamin_A' => $validated['Vitamin_A'],
                    'Deworming' => $validated['Deworming'],
                    'status' => 'imekamilika'
                ]);
            }

            // Get count of missed visits
            $missedVisits = VitaminAndDeworming::getMissedVisits($child);

            // Get remaining visits count
            $completedVisits = VitaminAndDeworming::where('child_id', $childId)
                ->where('status', 'imekamilika')
                ->count();

            $visitNumber = array_search($currentVisitPeriod, $visitSchedule) + 1;

            return response()->json([
                'message' => "Vitamin and Deworming record for visit {$visitNumber} ({$currentVisitPeriod} months) recorded successfully",
                'data' => [
                    'visit_number' => $visitNumber,
                    'visit_period' => $currentVisitPeriod . ' months',
                    'child_age' => $currentAgeInMonths . ' months',
                    'vitamin_a' => $record->Vitamin_A,
                    'deworming' => $record->Deworming,
                    'status' => $record->status,
                    'recorded_date' => $record->updated_at->format('Y-m-d H:i:s')
                ],
                'summary' => [
                    'completed_visits' => $completedVisits,
                    'missed_visits' => $missedVisits,
                    'remaining_visits' => 10 - $completedVisits
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating record',
                'error' => $e->getMessage()
            ], 500);
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
        try {
            $user = $request->user();

            $child = Child::where('user_id', $user->id)->first();
            if (!$child) {
                return response()->json([
                    'message' => 'Child not found'
                ], 404);
            }
            $records = VitaminAndDeworming::where('child_id', $child->id)->get(['created_at', 'Vitamin_A', 'Deworming', 'status']);
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

    /**
     * This method is for healthcare providers to view vitamin and deworming records by child ID
     */
    public function showByChildId($childId)
    {
        try {
            // Check if the child exists
            $child = Child::find($childId);
            if (!$child) {
                return response()->json([
                    'message' => 'Child not found'
                ], 404);
            }

            // Get all records ordered by date
            $allRecords = VitaminAndDeworming::where('child_id', $childId)
                ->orderBy('created_at', 'desc')
                ->get(['created_at', 'Vitamin_A', 'Deworming', 'status']);

            // Get past visits (up to current date)
            $pastRecords = $allRecords->filter(function ($record) {
                return Carbon::parse($record->created_at)->lte(Carbon::now());
            });

            // Calculate summary
            $dob = Carbon::parse($child->date_of_birth);
            $scheduledVisits = VitaminAndDeworming::getScheduledVisits($child);
            $missedVisits = VitaminAndDeworming::getMissedVisits($child);

            // Calculate remaining visits based on past records only
            $remainingVisits = 10 - $pastRecords->count();

            // Find next scheduled visit
            $nextVisit = null;
            if ($remainingVisits > 0) {
                $nextVisit = $allRecords->first(function ($record) {
                    return Carbon::parse($record->created_at)->gt(Carbon::now());
                });
            }

            return response()->json([
                'message' => 'Records fetched successfully',
                'data' => [
                    'child' => [
                        'name' => $child->childName,
                        'date_of_birth' => $child->date_of_birth,
                        'age_in_months' => $dob->diffInMonths(Carbon::now())
                    ],
                    'records' => $allRecords,
                    'summary' => [
                        'total_visits' => $pastRecords->count(),
                        'missed_visits' => $missedVisits,
                        'remaining_visits' => $remainingVisits,
                        'next_scheduled_visit' => $nextVisit ? Carbon::parse($nextVisit->created_at)->format('Y-m-d') : null
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
