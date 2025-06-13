<?php

namespace App\Http\Controllers;

use App\Models\Child;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ChildController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json([
                'status' => 'success',
                'message' => 'Children fetched successfully',
                'data' => Child::all(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching children',
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function calculateNextCheckup($dateOfBirth)
    {
        try {
            $currentDate = now();
            $nextCheckup = null;

            // Start from the first month after birth
            for ($month = 1; $month <= 16; $month++) {
                $checkupDate = \Carbon\Carbon::parse($dateOfBirth)->addMonths($month);
                if ($checkupDate->greaterThan($currentDate)) {
                    $nextCheckup = $checkupDate->toDateString();
                    break;
                }
            }

            return $nextCheckup;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error calculating next checkup',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getChildProfile(Request $request)
    {
        try {
            $user = $request->user();

            // Get the child based on the authenticated user
            $child = Child::where('user_id', $user->id)->with('growthRecords')->first();

            if (!$child) {
                return response()->json([
                    'error' => 'Child not found',
                ], 404);
            } else {
                $latestGrowthRecord = $child->latestGrowthRecord;
                $nextCheckup = $this->calculateNextCheckup($child->dateOfBirth);
                return response()->json([
                    'child' => [
                        'childName' => $child->childName,
                        'childNo' => $child->childNo,
                        'dateOfBirth' => $child->date_of_birth,
                        'gender' => $child->gender,
                        'fatherName' => $child->fatherName,
                        'birthWeight' => $child->birthWeight,
                        'weight' => $latestGrowthRecord ? $latestGrowthRecord->weight : null,
                        'height' => $latestGrowthRecord ? $latestGrowthRecord->height : null,
                        'nextCheckup' => $nextCheckup,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occurred',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function growthRecordSummary(Request $request)
    {
        try {
            $user = $request->user();

            // Get the child based on the authenticated user
            $child = Child::where('user_id', $user->id)->first();

            if (!$child) {
                return response()->json([
                    'error' => 'Child not found',
                ], 404);
            } else {
                return response()->json([
                    'growthRecordSummary' => [
                        'birthWeight' => $child->birthWeight,
                        'birthHeight' => $child->birthHeight,
                        'birthDate' => $child->date_of_birth, // fixed: use correct attribute
                        'weight' => $child->latestGrowthRecord ? $child->latestGrowthRecord->weight : null,
                        'height' => $child->latestGrowthRecord ? $child->latestGrowthRecord->height : null,
                        'nextCheckup' => $this->calculateNextCheckup($child->dateOfBirth),
                    ],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occurred',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function getParentProfile(Request $request)
    {
        try {
            $user = $request->user();

            // Get the child based on the authenticated user
            $child = Child::where('user_id', $user->id)->first();

            if (!$child) {
                return response()->json([
                    'error' => 'Child not found',
                ], 404);
            } else {
                return response()->json([
                    'parent' => [
                        'fatherName' => $child->fatherName,
                        'motherName' => $child->motherName,
                        'birthFacility' => $child->birthFacility,
                        'birthAttendant' => $child->birthAttendant, // Add this line
                        'email' => $child->email,
                        'phoneNo' => $child->phoneNo,
                        'motherAge' => $child->motherAge,
                        'address' => [
                            'street' => $child->address['street'] ?? null,
                            'ward' => $child->address['ward'] ?? null,
                            'Region' => $child->address['Region'] ?? null,
                        ]
                    ],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error Occurred',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function search(Request $request)
    {
        try {
            $query = Child::query();

            if ($request->has('q')) {
                $searchTerm = $request->input('q');

                $query->where(function ($q) use ($searchTerm) {
                    $q->where('childName', 'like', '%' . $searchTerm . '%')
                        ->orWhere('childNo', 'like', '%' . $searchTerm . '%')
                        ->orWhere('fatherName', 'like', '%' . $searchTerm . '%')
                        ->orWhere('motherName', 'like', '%' . $searchTerm . '%');
                });
            }

            // Add any filters you need
            if ($request->has('gender')) {
                $query->where('gender', $request->input('gender'));
            }

            $results = $query->limit(10)->get();

            return response()->json([
                'status' => 'success',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'childNo' => 'required|string|unique:children,childNo|max:255',
                'childName' => 'required|string|max:255',
                'date_of_birth' => 'required|date',
                'gender' => 'required|string|max:10',
                'birthWeight' => 'required|numeric',
                'birthHeight' => 'required|numeric',
                'fatherName' => 'required|string|max:255',
                'motherName' => 'required|string|max:255',
                'birthFacility' => 'required|string|max:255',
                'birthAttendant' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phoneNo' => 'nullable|string|max:20',
                'address' => 'required|array',
                'address.street' => 'required|string|max:255',
                'address.ward' => 'required|string|max:255',
                'address.Region' => 'required|string|max:255',
                'motherAge' => 'nullable|integer|min:0|max:120',
                'health_care_provider_id' => 'required|exists:health_care_providers,id'
            ]);

            // Create the user first
            $user = User::create([
                'name' => $validated['childName'],
                'username' => $validated['childNo'],
                'password' => Hash::make($validated['fatherName'])
            ]);
            $user->assignRole('child');
            $token = $user->createToken('auth_token')->plainTextToken;

            // Add user_id to the validated data for the child
            $childData = $validated;
            $childData['user_id'] = $user->id;
            unset($childData['health_care_provider_id']); // Remove pivot id from child data

            $child = Child::create($childData);

            // Attach the child to the health care provider via the pivot table
            $child->healthCareProviders()->attach($validated['health_care_provider_id']);

            // Send SMS to parent with credentials and vaccination info
            $this->sendWelcomeSms($child, $validated['phoneNo'], $validated['childNo'], $validated['fatherName']);

            // Generate appointments for the new child
            \Illuminate\Support\Facades\Artisan::call('appointments:generate', ['childId' => $child->id]);

            // Create Vitamin and Deworming sessions
            $dateOfBirth = $child->date_of_birth;
            for ($i = 0; $i < 10; $i++) {
                $sessionDate = \Carbon\Carbon::parse($dateOfBirth)->addMonths(6 * $i);
                \App\Models\VitaminAndDeworming::create([
                    'child_id' => $child->id,
                    'Vitamin_A' => false,
                    'Deworming' => false,
                    'status' => 'inasubiri',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Child created successfully. Appointments generated.',
                'child' => $child,
                'credentials_info' => [
                    'username' => $validated['childNo'],
                    'note' => 'Please use your child number as username and father\'s name as password to login.'
                ],
                'access_token' => $token,
                'token_type' => 'Bearer'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating child',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Child $child)
    {
        try {
            // Use the model binding to automatically fetch the child
            // Format the address data
            $formattedChild = [
                'id' => $child->id,
                'childNo' => $child->childNo,
                'childName' => $child->childName,
                'gender' => $child->gender,
                'date_of_birth' => $child->date_of_birth,
                'date_of_birth_formatted' => \Carbon\Carbon::parse($child->date_of_birth)->format('Y-m-d'),
                'fatherName' => $child->fatherName,
                'motherName' => $child->motherName,
                'address' => $child->address,
                'birthWeight' => $child->birthWeight,
                'birthHeight' => $child->birthHeight,
                'birthFacility' => $child->birthFacility,
                'birthAttendant' => $child->birthAttendant,
                'email' => $child->email,
                'phoneNo' => $child->phoneNo,
                'motherAge' => $child->motherAge
            ];

            return response()->json([
                'status' => 'success',
                'data' => $formattedChild
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching child details',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Child $child)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:children,id',
                'childName' => 'sometimes|string|max:255',
                'date_of_birth' => 'sometimes|date',
                'gender' => 'sometimes|string|max:10',
                'birthWeight' => 'sometimes|numeric',
                'fatherName' => 'sometimes|string|max:255',
                'motherName' => 'sometimes|string|max:255',
                'birthFacility' => 'sometimes|string|max:255',
                'birthAttendant' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
                'phoneNo' => 'sometimes|string|max:20',
                'address' => 'sometimes|array',
                'address.street' => 'sometimes|string|max:255',
                'address.ward' => 'sometimes|string|max:255',
                'address.Region' => 'sometimes|string|max:255',
                'motherAge' => 'sometimes|integer|min:0|max:120',
            ]);

            $child = Child::findOrFail($validated['id']);
            $child->fill($validated);
            $child->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Child updated successfully',
                'child' => $child
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating child',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Child $child)
    {
        //
    }

    /**
     * Send welcome SMS to parent with credentials and vaccination info
     */
    private function sendWelcomeSms($child, $phoneNo, $username, $password)
    {
        try {
            $smsService = new \App\Services\SmsService();
            $childName = $child->childName;
            $message = "Mzazi wa $childName, hongera! Akaunti yako imeundwa. Tumia namba ya mtoto ($username) kama jina la mtumiaji na jina la baba ($password) kama neno la siri kuingia. Baada ya kuzaliwa, mtoto atatakiwa kupokea chanjo zifuatazo: BCF, bOPVO.";
            if ($phoneNo) {
                $smsService->send($phoneNo, $message);
            }
        } catch (\Exception $e) {
            // Optionally log error
        }
    }

    /**
     * Get child profile data for healthcare providers
     */
    public function getHealthcareChildProfile($childId)
    {
        try {
            $child = Child::findOrFail($childId);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'childNo' => $child->childNo,
                    'childName' => $child->childName,
                    'gender' => $child->gender,
                    'date_of_birth' => $child->date_of_birth,
                    'fatherName' => $child->fatherName,
                    'motherName' => $child->motherName,
                    'address' => [
                        'ward' => $child->address['ward'] ?? null
                    ],
                    'phoneNo' => $child->phoneNo
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching child profile',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
