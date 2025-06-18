<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\HealthCareProvider;
use App\Models\Child;
use App\Models\Vaccination;
use App\Models\GrowthRecords;
use Carbon\Carbon;

class HealthcareReportController extends Controller
{
    /**
     * Generate daily activity report for authenticated healthcare provider
     */
    public function dailyReport(Request $request)
    {
        try {
            $user = $request->user();
            $healthCareProvider = HealthCareProvider::where('user_id', $user->id)->first();

            if (!$healthCareProvider) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Healthcare provider not found'
                ], 404);
            }

            $today = Carbon::today();

            // Get children registered today by this provider
            $children = Child::whereHas('healthCareProviders', function ($query) use ($healthCareProvider) {
                $query->where('health_care_provider_id', $healthCareProvider->id);
            })
                ->whereDate('created_at', $today)
                ->with(['user'])
                ->get();

            // Get vaccinations given today by this provider
            $vaccinations = Vaccination::where('health_care_provider_id', $healthCareProvider->id)
                ->whereDate('updated_at', $today)
                ->where('Hali', 'imekamilika')
                ->with(['child'])
                ->get();

            // Get growth records managed today by this provider
            $growthRecords = GrowthRecords::where('health_care_provider_id', $healthCareProvider->id)
                ->whereDate('created_at', $today)
                ->with(['child'])
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'provider' => [
                        'name' => $healthCareProvider->name,
                        'license' => $healthCareProvider->license,
                        'facility' => $healthCareProvider->facility
                    ],
                    'report_date' => $today->format('Y-m-d'),
                    'children' => $children,
                    'vaccinations' => $vaccinations,
                    'growth_records' => $growthRecords,
                    'summary' => [
                        'children_count' => $children->count(),
                        'vaccinations_count' => $vaccinations->count(),
                        'growth_records_count' => $growthRecords->count()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating daily report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate monthly activity report for authenticated healthcare provider
     */
    public function monthlyReport(Request $request)
    {
        try {
            $user = $request->user();
            $healthCareProvider = HealthCareProvider::where('user_id', $user->id)->first();

            if (!$healthCareProvider) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Healthcare provider not found'
                ], 404);
            }

            $currentMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // Get children registered this month by this provider
            $children = Child::whereHas('healthCareProviders', function ($query) use ($healthCareProvider) {
                $query->where('health_care_provider_id', $healthCareProvider->id);
            })
                ->whereBetween('created_at', [$currentMonth, $endOfMonth])
                ->with(['user'])
                ->get();

            // Get vaccinations given this month by this provider
            $vaccinations = Vaccination::where('health_care_provider_id', $healthCareProvider->id)
                ->whereBetween('updated_at', [$currentMonth, $endOfMonth])
                ->where('Hali', 'imekamilika')
                ->with(['child'])
                ->get();

            // Get growth records managed this month by this provider
            $growthRecords = GrowthRecords::where('health_care_provider_id', $healthCareProvider->id)
                ->whereBetween('created_at', [$currentMonth, $endOfMonth])
                ->with(['child'])
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'provider' => [
                        'name' => $healthCareProvider->name,
                        'license' => $healthCareProvider->license,
                        'facility' => $healthCareProvider->facility
                    ],
                    'report_period' => [
                        'start' => $currentMonth->format('Y-m-d'),
                        'end' => $endOfMonth->format('Y-m-d'),
                        'month_name' => $currentMonth->format('F Y')
                    ],
                    'children' => $children,
                    'vaccinations' => $vaccinations,
                    'growth_records' => $growthRecords,
                    'summary' => [
                        'children_count' => $children->count(),
                        'vaccinations_count' => $vaccinations->count(),
                        'growth_records_count' => $growthRecords->count()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating monthly report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate custom date range report for authenticated healthcare provider
     */
    public function customReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ]);

            $user = $request->user();
            $healthCareProvider = HealthCareProvider::where('user_id', $user->id)->first();

            if (!$healthCareProvider) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Healthcare provider not found'
                ], 404);
            }

            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = Carbon::parse($validated['end_date'])->endOfDay();

            // Get children registered in date range by this provider
            $children = Child::whereHas('healthCareProviders', function ($query) use ($healthCareProvider) {
                $query->where('health_care_provider_id', $healthCareProvider->id);
            })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->with(['user'])
                ->get();

            // Get vaccinations given in date range by this provider
            $vaccinations = Vaccination::where('health_care_provider_id', $healthCareProvider->id)
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->where('Hali', 'imekamilika')
                ->with(['child'])
                ->get();

            // Get growth records managed in date range by this provider
            $growthRecords = GrowthRecords::where('health_care_provider_id', $healthCareProvider->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->with(['child'])
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'provider' => [
                        'name' => $healthCareProvider->name,
                        'license' => $healthCareProvider->license,
                        'facility' => $healthCareProvider->facility
                    ],
                    'report_period' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d')
                    ],
                    'children' => $children,
                    'vaccinations' => $vaccinations,
                    'growth_records' => $growthRecords,
                    'summary' => [
                        'children_count' => $children->count(),
                        'vaccinations_count' => $vaccinations->count(),
                        'growth_records_count' => $growthRecords->count()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating custom report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export all data for authenticated healthcare provider
     */
    public function exportData(Request $request)
    {
        try {
            $user = $request->user();
            $healthCareProvider = HealthCareProvider::where('user_id', $user->id)->first();

            if (!$healthCareProvider) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Healthcare provider not found'
                ], 404);
            }

            // Get all children registered by this provider
            $children = Child::whereHas('healthCareProviders', function ($query) use ($healthCareProvider) {
                $query->where('health_care_provider_id', $healthCareProvider->id);
            })
                ->with(['user', 'vaccinations', 'growthRecords', 'appointments'])
                ->get();

            // Get all vaccinations given by this provider
            $vaccinations = Vaccination::where('health_care_provider_id', $healthCareProvider->id)
                ->with(['child'])
                ->get();

            // Get all growth records managed by this provider
            $growthRecords = GrowthRecords::where('health_care_provider_id', $healthCareProvider->id)
                ->with(['child'])
                ->get();

            $exportData = [
                'provider' => [
                    'name' => $healthCareProvider->name,
                    'license' => $healthCareProvider->license,
                    'facility' => $healthCareProvider->facility,
                    'userRole' => $healthCareProvider->userRole,
                    'contact' => $healthCareProvider->contact,
                    'export_date' => Carbon::now()->toISOString()
                ],
                'children' => $children,
                'vaccinations' => $vaccinations,
                'growth_records' => $growthRecords,
                'summary' => [
                    'total_children' => $children->count(),
                    'total_vaccinations' => $vaccinations->count(),
                    'total_growth_records' => $growthRecords->count()
                ]
            ];

            return response()->json($exportData, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="healthcare_data_export_' . date('Y-m-d') . '.json"'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error exporting data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get provider profile for settings
     */
    public function getProfile(Request $request)
    {
        try {
            $user = $request->user();
            $healthCareProvider = HealthCareProvider::where('user_id', $user->id)->first();

            if (!$healthCareProvider) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Healthcare provider not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $healthCareProvider->id,
                    'name' => $healthCareProvider->name,
                    'license' => $healthCareProvider->license,
                    'facility' => $healthCareProvider->facility,
                    'contact' => $healthCareProvider->contact,
                    'userRole' => $healthCareProvider->userRole,
                    'gender' => $healthCareProvider->gender,
                    'status' => $healthCareProvider->status
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send appointment SMS notifications
     */
    public function sendAppointmentSMS(Request $request)
    {
        try {
            $user = $request->user();
            $healthCareProvider = HealthCareProvider::where('user_id', $user->id)->first();

            if (!$healthCareProvider) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Healthcare provider not found'
                ], 404);
            }            // Trigger the appointment notifications command
            Artisan::call('appointments:send-notifications');

            $output = Artisan::output();

            return response()->json([
                'status' => 'success',
                'message' => 'Appointment SMS notifications have been sent successfully',
                'data' => [
                    'command_output' => $output,
                    'sent_by' => $healthCareProvider->name,
                    'sent_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error sending appointment SMS notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send food facts SMS notifications
     */
    public function sendFoodFactsSMS(Request $request)
    {
        try {
            $user = $request->user();
            $healthCareProvider = HealthCareProvider::where('user_id', $user->id)->first();

            if (!$healthCareProvider) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Healthcare provider not found'
                ], 404);
            }            // Trigger the food facts notifications command
            Artisan::call('foodfacts:send-notifications');

            $output = Artisan::output();

            return response()->json([
                'status' => 'success',
                'message' => 'Food facts SMS notifications have been sent successfully',
                'data' => [
                    'command_output' => $output,
                    'sent_by' => $healthCareProvider->name,
                    'sent_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error sending food facts SMS notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
