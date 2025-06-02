<?php

namespace App\Http\Controllers;

use App\Models\Vaccination;
use App\Models\VaccinationVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Child;
use App\Models\User;

class VaccinationController extends Controller
{
    const MAX_VACCINATION_NO_USES = 3;

    // Get vaccinations for child
    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'child_id' => 'required|exists:children,id'
            ]);

            return Vaccination::where('child_id', $validated['child_id'])
                ->get();
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Create new vaccination
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'child_id' => 'required|exists:children,id',
                'health_care_provider_id' => 'required|exists:health_care_providers,id',
                // 'status' => 'required|boolean',
                'vaccination_code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::exists('vaccinations', 'vaccination_code') // Ensure vaccination_code exists in the database
                ],
                'vaccination_no' => [
                    'nullable',
                    'string',
                    'max:50',
                    function ($attr, $value, $fail) use ($request) {
                        $count = Vaccination::where('vaccination_no', $value)->count();
                        if ($count >= self::MAX_VACCINATION_NO_USES) {
                            $fail("This vaccination number has reached the maximum usage limit (3 times)");
                        }

                        $existingRecord = Vaccination::where('vaccination_no', $value)->first();
                        if ($existingRecord && $existingRecord->vaccination_code !== $request->vaccination_code) {
                            $fail("This vaccination number is already assigned to the vaccination code '{$existingRecord->vaccination_code}'.");
                        }
                    }
                ]
            ]);

            // Automatically set status based on vaccination_no presence
            // $validated['status'] = !empty($validated['vaccination_no']);

            // $vaccination = Vaccination::create($validated + ['status' => true]);

            $vaccination = Vaccination::where('vaccination_code', $validated['vaccination_code'])
                ->whereNull('vaccination_no') // Ensure it's a seeded record with vaccination_no as null
                ->where('status', 0) // Ensure the status is 0 (not used yet)
                ->first();

            if (!$vaccination) {
                return response()->json([
                    'error' => 'No available record found for the given vaccination code.'
                ], 404);
            }

            // Update the seeded record with the new data
            $vaccination->update([
                'child_id' => $validated['child_id'],
                'vaccination_no' => $validated['vaccination_no'],
                'status' => 1 // Mark as used
            ]);


            $usesRemaining = self::MAX_VACCINATION_NO_USES -
                Vaccination::where('vaccination_no', $validated['vaccination_no'])->count();

            DB::commit();

            return response()->json([
                'message' => 'Vaccination recorded successfully',
                'data' => $vaccination,
                'uses_remaining' => $usesRemaining
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Store vaccination with parent verification via SMS (no Redis, uses DB)
    public function storeWithVerification(Request $request)
    {
        $validated = $request->validate([
            'child_id' => 'required|exists:children,id',
            'health_care_provider_id' => 'required|exists:health_care_providers,id',
            'vaccination_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('vaccinations', 'vaccination_code')
            ],
            'vaccination_no' => [
                'nullable',
                'string',
                'max:50',
            ]
        ]);

        $child = Child::find($validated['child_id']);
        if (!$child) {
            return response()->json(['error' => 'Child not found'], 404);
        }
        $parent = $child->user;
        if (!$parent || empty($child->phoneNo)) {
            return response()->json(['error' => 'Parent or phone number not found'], 404);
        }

        $verificationCode = random_int(100000, 999999);
        $smsMessage = "Confirm your child's vaccination (code: {$validated['vaccination_code']}). Verification code: $verificationCode. Please provide this code to the healthcare provider if you agree.";

        // Send SMS
        $smsService = new \App\Services\SmsService();
        $smsSent = $smsService->send($child->phoneNo, $smsMessage);
        if (!$smsSent) {
            return response()->json(['error' => 'Failed to send SMS'], 500);
        }

        // Store verification in DB (expires in 10 minutes)
        $expiresAt = now()->addMinutes(10);
        VaccinationVerification::create([
            'child_id' => $validated['child_id'],
            'health_care_provider_id' => $validated['health_care_provider_id'],
            'vaccination_code' => $validated['vaccination_code'],
            'vaccination_no' => $validated['vaccination_no'],
            'verification_code' => $verificationCode,
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'message' => 'Verification code sent to parent. Awaiting confirmation.',
            'verification_code' => $verificationCode // For testing/demo only; remove in production
        ], 200);
    }

    // Endpoint to verify code and store vaccination (no Redis, uses DB)
    public function verifyAndStoreVaccination(Request $request)
    {
        $validated = $request->validate([
            'child_id' => 'required|exists:children,id',
            'vaccination_code' => 'required|string|max:50',
            'verification_code' => 'required|digits:6',
        ]);
        $verification = VaccinationVerification::where('child_id', $validated['child_id'])
            ->where('vaccination_code', $validated['vaccination_code'])
            ->where('verification_code', $validated['verification_code'])
            ->where('expires_at', '>', now())
            ->first();
        if (!$verification) {
            return response()->json(['error' => 'No pending verification found, code expired, or invalid code.'], 404);
        }
        DB::beginTransaction();
        try {
            $vaccination = Vaccination::where('vaccination_code', $verification->vaccination_code)
                ->whereNull('vaccination_no')
                ->where('status', 0)
                ->first();
            if (!$vaccination) {
                DB::rollBack();
                return response()->json(['error' => 'No available record found for the given vaccination code.'], 404);
            }
            $vaccination->update([
                'child_id' => $verification->child_id,
                'vaccination_no' => $verification->vaccination_no,
                'status' => 1,
                'health_care_provider_id' => $verification->health_care_provider_id
            ]);
            // Delete verification row after use
            $verification->delete();
            DB::commit();
            return response()->json([
                'message' => 'Vaccination recorded successfully after verification.',
                'data' => $vaccination
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // Show vaccination details to the healthcare provider
    public function show(Request $request)
    {
        try {
            $validated = $request->validate([
                'child_id' => 'required|exists:children,id'
            ]);

            $Data = Vaccination::where('child_id', $validated['child_id'])
                ->get(['vaccination_code', 'created_at', 'status']);
            return response()->json([
                'message' => 'Vaccination records fetched successfully',
                'data' => $Data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    //Show vaccination details to the parent
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

            $Data = Vaccination::where('child_id', $child->id)
                ->get(['vaccination_code', 'created_at', 'status']);
            return response()->json([
                'message' => 'Vaccination records fetched successfully',
                'data' => $Data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Get vaccination status report
    public function statusReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'child_id' => 'required|exists:children,id'
            ]);

            $received = Vaccination::where('child_id', $validated['child_id'])
                ->get()
                ->keyBy('vaccination_code');

            $allCodes = config('vaccination.codes', []);

            $report = collect($allCodes)->map(function ($code) use ($received) {
                $record = $received[$code] ?? null;
                return [
                    'vaccination_code' => $code,
                    'received' => !is_null($record),
                    'vaccination_no' => $record->vaccination_no ?? null,
                    'date' => optional($record)->created_at
                ];
            });

            return response()->json($report);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Check vaccination_no availability
    public function checkAvailability(Request $request)
    {
        try {
            $validated = $request->validate([
                'vaccination_no' => 'required|string|max:50'
            ]);

            $count = Vaccination::where('vaccination_no', $validated['vaccination_no'])
                ->count();

            return response()->json([
                'available' => $count < self::MAX_VACCINATION_NO_USES,
                'uses_remaining' => self::MAX_VACCINATION_NO_USES - $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Update vaccination
    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'id' => 'required|exists:vaccinations,id',
                'vaccination_code' => 'sometimes|string|max:50',
                'vaccination_no' => [
                    'sometimes',
                    'string',
                    'max:50',
                    function ($attr, $value, $fail) use ($request) {
                        $count = Vaccination::where('vaccination_no', $value)
                            ->where('id', '!=', $request->id)
                            ->count();
                        if ($count >= self::MAX_VACCINATION_NO_USES) {
                            $fail("This vaccination number has reached maximum uses");
                        }
                    }
                ]
            ]);

            $vaccination = Vaccination::findOrFail($validated['id']);
            $vaccination->update($validated);

            DB::commit();

            return response()->json([
                'message' => 'Vaccination updated successfully',
                'data' => $vaccination
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    // Delete vaccination
    public function destroy(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:vaccinations,id'
            ]);

            Vaccination::findOrFail($validated['id'])->delete();

            return response()->json([
                'message' => 'Vaccination deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
