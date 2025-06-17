<?php

namespace App\Http\Controllers;

use App\Models\GrowthRecords;  
use Illuminate\Http\Request;  
use App\Models\Child;  
use App\Services\ChildGrowthAssessment;  
use App\Services\SmsService;

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
                'data' => GrowthRecords::with('healthCareProvider')->get(),
            ]);
        } catch (\Exception $e) {
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
                ->with('healthCareProvider')
                ->get()
                ->groupBy(function ($record) {
                    return \Carbon\Carbon::parse($record->created_at)->format('Y-m'); // Group by month (YYYY-MM)
                })
                ->map(function ($records, $month) {
                    return [
                        'month' => $month,
                        'weights' => $records->pluck('weight'), // Collect all weights for the month
                        'health_care_providers' => $records->pluck('healthCareProvider')->unique('id')->values(),
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
            if (!$user->hasRole('child')) {
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
                ->with('healthCareProvider')
                ->get()
                ->groupBy(function ($record) {
                    return \Carbon\Carbon::parse($record->created_at)->format('Y-m'); // Group by month (YYYY-MM)
                })
                ->map(function ($records, $month) {
                    return [
                        'month' => $month,
                        'weights' => $records->pluck('weight'),
                        'health_care_providers' => $records->pluck('healthCareProvider')->unique('id')->values(),
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

    /**
     * Get the growth chart for a child by child ID (from route parameter).
     *
     * @param int $childId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGrowthChartByChildId($childId)
    {
        try {
            // Validate that the child exists
            $childExists = Child::where('id', $childId)->exists();
            if (!$childExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Child not found.',
                ], 404);
            }

            $growthRecords = GrowthRecords::where('child_id', $childId)
                ->with('healthCareProvider')
                ->get()
                ->groupBy(function ($record) {
                    return \Carbon\Carbon::parse($record->created_at)->format('Y-m'); // Group by month (YYYY-MM)
                })
                ->map(function ($records, $month) {
                    return [
                        'month' => $month,
                        'weights' => $records->pluck('weight'),
                        'health_care_providers' => $records->pluck('healthCareProvider')->unique('id')->values(),
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
     * Store a newly created resource in storage.
     * 
     * @param Request $request
     * @param int $childId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $childId)
    {
        try {
            // First validate that the child exists
            $childExists = Child::where('id', $childId)->exists();
            if (!$childExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Child not found.',
                ], 404);
            }

            $validated = $request->validate([
                'health_care_provider_id' => 'required|exists:health_care_providers,id',
                'weight' => 'required|numeric',
                'height' => 'required|numeric',
            ]);

            // Restrict to one record per child per month
            $existing = GrowthRecords::where('child_id', $childId)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->exists();
            if ($existing) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data has already been entered for this child for the current month.'
                ], 409);
            }

            $growthRecord = GrowthRecords::create([
                'child_id' => $childId,
                'health_care_provider_id' => $validated['health_care_provider_id'],
                'weight' => $validated['weight'],
                'height' => $validated['height'],
            ]);

            // Get child's birth date and gender
            $child = Child::find($childId);
            if (!$child) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Child not found.',
                ], 404);
            }

            // Assess growth
            $growthAssessment = ChildGrowthAssessment::assessGrowth(
                $validated['weight'],
                $child->date_of_birth,
                $child->gender
            );

            logger('Child birth date: ' . $child->date_of_birth);
            logger('Child gender: ' . $child->gender);
            logger('Growth assessment: ' . json_encode($growthAssessment));
            logger('Child phone number: ' . $child->phoneNo);

            // Send SMS if underweight or overweight
            if ($growthAssessment['status'] == 'Underweight' || $growthAssessment['status'] == 'Overweight'  || $growthAssessment['status'] == 'Severe Underweight') {
                logger('Sending SMS');
                if ($child->phoneNo) {
                    $smsService = new SmsService();
                    
                    // Translate status and recommendations to Swahili
                    $swahiliStatus = $this->translateStatusToSwahili($growthAssessment['status']);
                    $swahiliRecommendation = $this->translateRecommendationToSwahili($growthAssessment['status'], $growthAssessment['recommendation']);
                    
                    $message = "Mtoto wako ana {$swahiliStatus}. Mapendekezo: {$swahiliRecommendation}";
                    $smsSent = $smsService->send($child->phoneNo, $message);
                } else {
                    $smsSent = false;
                }
            } else {
                logger('Not sending SMS');
                $smsSent = false;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data stored successfully',
                'data' => $growthRecord,
                'growth_assessment' => $growthAssessment,
                'sms_sent' => (bool)$smsSent,
            ]);
        } catch (\Exception $e) {
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
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'height' => 'required|numeric',
                'weight' => 'required|numeric',
            ]);

            $growthRecord = GrowthRecords::find($id);

            if (!$growthRecord) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Growth record not found.',
                ], 404);
            }

            $growthRecord->height = $validated['height'];
            $growthRecord->weight = $validated['weight'];
            $growthRecord->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Growth record updated successfully.',
                'data' => $growthRecord,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating growth record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GrowthRecords $growthRecords)
    {
        //
    }

    /**
     * Get specific growth record details for a child.
     *
     * @param int $childId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGrowthRecordDetails($childId)
    {
        try {
            $child = Child::find($childId);
            if (!$child) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Child not found.',
                ], 404);
            }

            $growthRecords = GrowthRecords::where('child_id', $childId)
                ->select('id', 'height', 'weight', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($record) use ($child) {
                    return [
                        'id' => $record->id,
                        'child_no' => $child->childNo,
                        'height' => $record->height,
                        'weight' => $record->weight,
                        'date' => $record->created_at->format('Y-m-d')
                    ];
                });

            if ($growthRecords->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No growth records found for the specified child.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Growth record details fetched successfully.',
                'data' => $growthRecords
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching growth record details.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Translate growth status to Swahili
     *
     * @param string $status
     * @return string
     */
    private function translateStatusToSwahili($status)
    {
        $translations = [
            'Underweight' => 'upungufu wa uzito',
            'Severe Underweight' => 'upungufu mkubwa wa uzito',
            'Overweight' => 'uzito wa ziada',
            'Normal' => 'uzito wa kawaida',
            'Obese' => 'unene kupita kiasi'
        ];

        return $translations[$status] ?? strtolower($status);
    }

    /**
     * Translate recommendations to Swahili based on status
     *
     * @param string $status
     * @param string $recommendation
     * @return string
     */
    private function translateRecommendationToSwahili($status, $recommendation)
    {
        $swahiliRecommendations = [
            'Underweight' => 'Mpe mtoto chakula chenye protini na kalori nyingi. Tembelea kituo cha afya kwa ushauri zaidi.',
            'Severe Underweight' => 'Hali hii ni hatari! Peleka mtoto hospitalini haraka kwa matibabu ya dharura.',
            'Overweight' => 'Punguza chakula chenye mafuta na sukari. Ongeza michezo na mazoezi. Tembelea daktari kwa ushauri.',
            'Normal' => 'Endelea kumpa mtoto chakula cha lishe bora na mazoezi ya kawaida.',
            'Obese' => 'Mtoto ana unene kupita kiasi. Tembelea daktari haraka kwa mpango wa kupunguza uzito.'
        ];

        return $swahiliRecommendations[$status] ?? 'Tembelea kituo cha afya kwa ushauri zaidi.';
    }
}
