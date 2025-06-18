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
                'vaccination_codes' => [
                    'required',
                    'array',
                    'min:1', // Ensure at least one vaccination code is present
                ],
                'vaccination_codes.*' => [
                    'string',
                    'max:50',
                    Rule::exists('vaccinations', 'vaccination_code') // Ensure vaccination_code exists in the database
                ],
                'vaccination_no' => [
                    'nullable',
                    'string',
                    'max:50',
                    function ($attr, $value, $fail) use ($request) {
                        if ($value) {
                            $count = Vaccination::where('vaccination_no', $value)->count();
                            if ($count >= self::MAX_VACCINATION_NO_USES) {
                                $fail("This vaccination number has reached the maximum usage limit (" . self::MAX_VACCINATION_NO_USES . " times)");
                            }

                            // Check if the vaccination number is already assigned to a different vaccination code
                            $existingRecord = Vaccination::where('vaccination_no', $value)->first();
                            if ($existingRecord && !in_array($existingRecord->vaccination_code, $request->vaccination_codes)) {
                                $fail("This vaccination number is already assigned to the vaccination code '{$existingRecord->vaccination_code}'.");
                            }
                        }
                    }
                ]
            ]);

            $vaccinations = [];
            $vaccinationNos = $request->input('vaccination_nos', []);

            if (count($validated['vaccination_codes']) !== count($vaccinationNos)) {
                return response()->json(['error' => 'The number of vaccination codes must match the number of vaccination numbers.'], 400);
            }

            foreach ($validated['vaccination_codes'] as $key => $vaccinationCode) {
                $vaccinationNo = $vaccinationNos[$key] ?? null;

                $vaccination = Vaccination::where('vaccination_code', $vaccinationCode)
                    ->whereNull('vaccination_no')
                    ->where('Hali', 'inasubiri')
                    ->first();

                if (!$vaccination) {
                    DB::rollBack();
                    return response()->json(['error' => 'No available record found for the given vaccination code: ' . $vaccinationCode], 404);
                }

                $vaccination->update([
                    'child_id' => $validated['child_id'],
                    'vaccination_no' => $vaccinationNo,
                    'Hali' => 'imekamilika',
                    'health_care_provider_id' => $validated['health_care_provider_id']
                ]);

                $vaccinations[] = $vaccination;
            }

            DB::commit();

            return response()->json([
                'message' => 'Vaccinations recorded successfully',
                'data' => $vaccinations
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

    // Improved storeWithVerification function
    public function storeWithVerification(Request $request)
    {
        try {
            $validated = $request->validate([
                'child_id' => 'required|exists:children,id',
                'health_care_provider_id' => 'required|exists:health_care_providers,id',
                'vaccination_codes' => [
                    'required',
                    'array',
                    'min:1', // Ensure at least one vaccination code is present
                ],
                'vaccination_codes.*' => [
                    'string',
                    'max:50',
                    Rule::exists('vaccinations', 'vaccination_code')
                ],
                'vaccination_nos' => [
                    'required',
                    'array',
                    'min:1', // Ensure at least one vaccination number is present
                ],
                'vaccination_nos.*' => [
                    'string',
                    'max:50',
                    function ($attr, $value, $fail) use ($request) {
                        if ($value) {
                            $count = Vaccination::where('vaccination_no', $value)->count();
                            if ($count >= self::MAX_VACCINATION_NO_USES) {
                                $fail("This vaccination number has reached the maximum usage limit (" . self::MAX_VACCINATION_NO_USES . " times)");
                            }

                            // Check if the vaccination number is already assigned to a different vaccination code
                            $existingRecord = Vaccination::where('vaccination_no', $value)->first();
                            if ($existingRecord && !in_array($existingRecord->vaccination_code, $request->vaccination_codes)) {
                                $fail("This vaccination number is already assigned to the vaccination code '{$existingRecord->vaccination_code}'.");
                            }
                        }
                    }
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        }

        // Additional validation: Check for duplicate vaccination numbers within the same request
        $vaccinationNos = $request->input('vaccination_nos', []);
        $duplicateNos = array_diff_assoc($vaccinationNos, array_unique($vaccinationNos));
        if (!empty($duplicateNos)) {
            return response()->json([
                'error' => 'Duplicate vaccination numbers detected: ' . implode(', ', array_values(array_unique($duplicateNos)))
            ], 422);
        }

        // Additional validation: Check the "Hali" status of vaccinations for the specific child
        $vaccinationCodes = $validated['vaccination_codes'];
        $completedVaccinations = [];
        $unavailableVaccinations = [];
        $notFoundVaccinations = [];

        foreach ($vaccinationCodes as $vaccinationCode) {
            $vaccination = Vaccination::where('vaccination_code', $vaccinationCode)
                ->where('child_id', $validated['child_id'])
                ->first();

            if (!$vaccination) {
                $notFoundVaccinations[] = $vaccinationCode;
                continue;
            }

            // Check if vaccination is already completed
            if ($vaccination->Hali === 'imekamilika') {
                $completedVaccinations[$vaccinationCode] = [
                    "The vaccination '{$vaccinationCode}' has already been completed for this child. Vaccination number: {$vaccination->vaccination_no}"
                ];
                continue;
            }

            // Check if vaccination is not available for administration
            if ($vaccination->Hali !== 'inasubiri' && $vaccination->Hali !== 'amekosa') {
                $unavailableVaccinations[$vaccinationCode] = [
                    "The vaccination '{$vaccinationCode}' is not available for administration. Current status: {$vaccination->Hali}"
                ];
            }
        }

        // If there are any validation errors, return them all
        $allErrors = array_merge($completedVaccinations, $unavailableVaccinations);

        if (!empty($notFoundVaccinations)) {
            foreach ($notFoundVaccinations as $code) {
                $allErrors[$code] = ["No vaccination record found for vaccination code: {$code} for this child"];
            }
        }

        if (!empty($allErrors)) {
            return response()->json([
                'error' => 'Vaccination validation failed',
                'messages' => $allErrors,
                'summary' => [
                    'completed_count' => count($completedVaccinations),
                    'unavailable_count' => count($unavailableVaccinations),
                    'not_found_count' => count($notFoundVaccinations),
                    'total_errors' => count($allErrors)
                ]
            ], 422);
        }

        $child = Child::find($validated['child_id']);
        if (!$child) {
            return response()->json([
                'error' => 'Child not found'
            ], 404);
        }
        $parent = $child->user;
        if (!$parent || empty($child->phoneNo)) {
            return response()->json([
                'error' => 'Parent or phone number not found'
            ], 404);
        }

        $vaccinationCodes = $validated['vaccination_codes'];
        $verificationCode = random_int(100000, 999999);
        $vaccinationList = implode(', ', $vaccinationCodes);
        $smsMessage = "Thibitisha chanjo za mtoto wako (jina: {$vaccinationList}). Nambari ya uthibitisho: $verificationCode. Tafadhali mpe mtoa huduma ya afya nambari hii ikiwa unakubali.";

        // Send SMS
        $smsService = new \App\Services\SmsService();
        $smsSent = $smsService->send($child->phoneNo, $smsMessage);
        if (!$smsSent) {
            return response()->json([
                'error' => 'Failed to send SMS'
            ], 500);
        }

        // Store verification in DB (expires in 10 minutes)
        $expiresAt = now()->addMinutes(10);
        $vaccinationNos = $request->input('vaccination_nos', []);

        if (count($vaccinationCodes) !== count($vaccinationNos)) {
            return response()->json(['error' => 'The number of vaccination codes must match the number of vaccination numbers.'], 400);
        }

        $verificationData = [];
        foreach ($vaccinationCodes as $key => $vaccinationCode) {
            $vaccinationNo = $vaccinationNos[$key] ?? null;

            $verificationData[] = [
                'child_id' => $validated['child_id'],
                'health_care_provider_id' => $validated['health_care_provider_id'],
                'vaccination_code' => $vaccinationCode,
                'vaccination_nos' => json_encode([$vaccinationNo]),
                'verification_code' => $verificationCode,
                'expires_at' => $expiresAt,
            ];
        }
        VaccinationVerification::insert($verificationData);

        return response()->json([
            'message' => 'Verification code sent to parent. Awaiting confirmation.',
            'verification_code' => $verificationCode, // For testing/demo only; remove in production
            'sms_message' => $smsMessage, // For testing/demo only; remove in production
        ], 200);
    }

    // Endpoint to verify code and store vaccination (no Redis, uses DB)
    public function verifyAndStoreVaccination(Request $request)
    {
        try {
            $validated = $request->validate([
                'child_id' => 'required|exists:children,id',
                'vaccination_codes' => [
                    'required',
                    'array',
                    'min:1', // Ensure at least one vaccination code is present
                ],
                'vaccination_codes.*' => [
                    'string',
                    'max:50',
                ],
                'vaccination_nos' => [
                    'required',
                    'array',
                    'min:1', // Ensure at least one vaccination number is present
                ],
                'vaccination_nos.*' => [
                    'string',
                    'max:50',
                    function ($attr, $value, $fail) use ($request) {
                        if ($value) {
                            $count = Vaccination::where('vaccination_no', $value)->count();
                            if ($count >= self::MAX_VACCINATION_NO_USES) {
                                $fail("This vaccination number has reached the maximum usage limit (" . self::MAX_VACCINATION_NO_USES . " times)");
                            }

                            // Check if the vaccination number is already assigned to a different vaccination code
                            $existingRecord = Vaccination::where('vaccination_no', $value)->first();
                            if ($existingRecord && !in_array($existingRecord->vaccination_code, $request->vaccination_codes)) {
                                $fail("This vaccination number is already assigned to the vaccination code '{$existingRecord->vaccination_code}'.");
                            }
                        }
                    }
                ],
                'verification_code' => 'required|digits:6',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        }

        // Additional validation: Check for duplicate vaccination numbers within the same request
        $vaccinationNos = $request->input('vaccination_nos', []);
        $duplicateNos = array_diff_assoc($vaccinationNos, array_unique($vaccinationNos));
        if (!empty($duplicateNos)) {
            return response()->json([
                'error' => 'Duplicate vaccination numbers detected: ' . implode(', ', array_values(array_unique($duplicateNos)))
            ], 422);
        }

        DB::beginTransaction();
        try {
            $vaccinations = [];

            if (count($validated['vaccination_codes']) !== count($vaccinationNos)) {
                return response()->json([
                    'error' => 'The number of vaccination codes must match the number of vaccination numbers'
                ], 400);
            }

            foreach ($validated['vaccination_codes'] as $key => $vaccinationCode) {
                $vaccinationNo = $vaccinationNos[$key] ?? null;

                $verification = VaccinationVerification::where('child_id', $validated['child_id'])
                    ->where('vaccination_code', $vaccinationCode)
                    ->where('verification_code', $validated['verification_code'])
                    ->where('expires_at', '>', now())
                    ->first();

                if (!$verification) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'No pending verification found, code expired, or invalid code for vaccination code: ' . $vaccinationCode
                    ], 404);
                }

                $vaccination = Vaccination::where('vaccination_code', $vaccinationCode)
                    ->where('child_id', $validated['child_id'])
                    ->whereNull('vaccination_no')
                    ->whereIn('Hali', ['inasubiri', 'amekosa'])
                    ->first();

                if (!$vaccination) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'No available vaccination record found for child ' . $validated['child_id'] . ' with vaccination code: ' . $vaccinationCode . '. The vaccination may already be completed or does not exist for this child.'
                    ], 404);
                }

                $vaccination->update([
                    'vaccination_no' => $vaccinationNo,
                    'Hali' => 'imekamilika',
                    'health_care_provider_id' => $verification->health_care_provider_id
                ]);

                // Delete verification row after use
                $verification->delete();

                $vaccinations[] = $vaccination;
            }

            DB::commit();

            // Log vaccination activity
            $child = Child::find($validated['child_id']);
            $vaccinationList = implode(', ', $validated['vaccination_codes']);
            SystemLogsController::logActivity(
                $request->user(),
                'Record Vaccination',
                "Completed vaccination for {$child->childName}: {$vaccinationList}",
                'success',
                'create',
                $request
            );

            return response()->json([
                'message' => 'Vaccination recorded successfully after verification.',
                'data' => $vaccinations
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // Show vaccination details to the healthcare provider
    public function show($childId)
    {
        try {
            // Validate that the child exists
            $child = Child::findOrFail($childId);

            $Data = Vaccination::where('child_id', $childId)
                ->get(['vaccination_code', 'updated_at', 'Hali', 'vaccination_no']);
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
                ->get(['vaccination_code', 'updated_at', 'Hali']);
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

    // Get vaccination Hali report
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
            ], 200);
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
            ], 200);
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
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
