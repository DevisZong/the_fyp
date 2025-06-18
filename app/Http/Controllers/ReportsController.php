<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Child;
use App\Models\HealthCareProvider;
use App\Models\User;
use App\Models\GrowthRecords;
use App\Models\Vaccination;
use App\Models\ActivityLog;
use App\Models\VitaminAndDeworming;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Get system summary statistics
     */
    public function getSystemSummary()
    {
        try {
            $totalHealthcareProviders = HealthCareProvider::count();
            $totalChildren = Child::count();
            // Count unique parents from children records
            $totalParents = Child::whereNotNull('motherName')->distinct('motherName')->count();
            $totalUsers = User::count();

            $systemInfo = [
                'system_name' => config('app.name', 'CVGMS'),
                'version' => '1.0.0',
                'last_backup' => '2024-06-18',
                'backup_frequency' => 'Daily',
                'system_status' => 'Online',
                'last_checked' => now()->format('Y-m-d H:i:s'),
                'support_contact' => 'admin@cvgms.com'
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_healthcare_providers' => $totalHealthcareProviders,
                    'total_children' => $totalChildren,
                    'total_parents' => $totalParents,
                    'total_users' => $totalUsers,
                    'system_info' => $systemInfo
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch system summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get healthcare providers report
     */
    public function getHealthcareProvidersReport()
    {
        try {
            $providers = HealthCareProvider::with('user')
                ->select('id', 'name', 'license', 'userRole', 'facility', 'contact', 'gender', 'status', 'created_at')
                ->get()
                ->map(function ($provider) {
                    return [
                        'id' => $provider->id,
                        'name' => $provider->name,
                        'license' => $provider->license,
                        'role' => $provider->userRole,
                        'facility' => $provider->facility,
                        'contact' => $provider->contact,
                        'gender' => $provider->gender,
                        'status' => $provider->status ? 'Active' : 'Inactive',
                        'joined_date' => $provider->created_at->format('Y-m-d')
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $providers,
                'total' => $providers->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch healthcare providers report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get children registration report
     */
    public function getChildrenReport()
    {
        try {
            $children = Child::select('id', 'childNo', 'childName', 'date_of_birth', 'gender', 'motherName', 'fatherName', 'birthFacility', 'created_at')
                ->get()
                ->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'child_no' => $child->childNo,
                        'name' => $child->childName,
                        'date_of_birth' => $child->date_of_birth,
                        'age' => $child->date_of_birth ? Carbon::parse($child->date_of_birth)->age : 'N/A',
                        'gender' => $child->gender,
                        'mother_name' => $child->motherName,
                        'father_name' => $child->fatherName,
                        'birth_facility' => $child->birthFacility,
                        'registration_date' => $child->created_at->format('Y-m-d')
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $children,
                'total' => $children->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch children report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get growth records report
     */
    public function getGrowthRecordsReport()
    {
        try {
            $growthRecords = GrowthRecords::with(['child', 'healthCareProvider'])
                ->select('id', 'child_id', 'health_care_provider_id', 'weight', 'height', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'child_name' => $record->child ? $record->child->childName : 'N/A',
                        'child_no' => $record->child ? $record->child->childNo : 'N/A',
                        'weight_kg' => $record->weight,
                        'height_cm' => $record->height,
                        'provider_name' => $record->healthCareProvider ? $record->healthCareProvider->name : 'N/A',
                        'date' => $record->created_at->format('Y-m-d'),
                        'action' => 'Growth Assessment'
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $growthRecords,
                'total' => $growthRecords->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch growth records report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get vaccination records report
     */
    public function getVaccinationReport()
    {
        try {
            $vaccinations = Vaccination::with(['child', 'healthCareProvider'])
                ->select('id', 'child_id', 'health_care_provider_id', 'vaccination_code', 'vaccination_no', 'Hali', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($vaccination) {
                    $child = $vaccination->child;
                    $age = 'N/A';
                    if ($child && $child->date_of_birth) {
                        $age = Carbon::parse($child->date_of_birth)->age . ' years';
                    }

                    return [
                        'id' => $vaccination->id,
                        'child_name' => $child ? $child->childName : 'N/A',
                        'child_no' => $child ? $child->childNo : 'N/A',
                        'age' => $age,
                        'provider_name' => $vaccination->healthCareProvider ? $vaccination->healthCareProvider->name : 'N/A',
                        'date' => $vaccination->created_at->format('Y-m-d'),
                        'vaccination_code' => $vaccination->vaccination_code,
                        'vaccination_no' => $vaccination->vaccination_no,
                        'status' => $vaccination->Hali ?? 'N/A',
                        'action' => 'Vaccination Administered'
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $vaccinations,
                'total' => $vaccinations->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch vaccination report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system activity logs
     */
    public function getActivityLogsReport()
    {
        try {
            $logs = ActivityLog::orderBy('created_at', 'desc')
                ->take(100) // Limit to last 100 logs
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'timestamp' => $log->created_at->format('Y-m-d H:i:s'),
                        'user_role' => $log->role,
                        'user_name' => $log->user_name,
                        'action' => $log->action,
                        'status' => $log->status
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $logs,
                'total' => $logs->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch activity logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get vitamin and deworming report
     */
    public function getVitaminDewormingReport()
    {
        try {
            $records = VitaminAndDeworming::with(['child'])
                ->select('id', 'child_id', 'Vitamin_A', 'Deworming', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($record) {
                    $child = $record->child;
                    $age = 'N/A';
                    if ($child && $child->date_of_birth) {
                        $age = Carbon::parse($child->date_of_birth)->age . ' years';
                    }

                    $treatment = [];
                    if ($record->Vitamin_A) $treatment[] = 'Vitamin A';
                    if ($record->Deworming) $treatment[] = 'Deworming';

                    return [
                        'id' => $record->id,
                        'child_name' => $child ? $child->childName : 'N/A',
                        'child_no' => $child ? $child->childNo : 'N/A',
                        'age' => $age,
                        'date' => $record->created_at->format('Y-m-d'),
                        'vitamin_a' => $record->Vitamin_A ? 'Yes' : 'No',
                        'deworming' => $record->Deworming ? 'Yes' : 'No',
                        'treatment' => implode(', ', $treatment) ?: 'None',
                        'status' => $record->status ?? 'N/A',
                        'action' => 'Vitamin/Deworming Administered'
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $records,
                'total' => $records->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch vitamin/deworming report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive system statistics
     */
    public function getSystemStatistics()
    {
        try {
            $stats = [
                'registrations' => [
                    'total_children' => Child::count(),
                    'children_this_month' => Child::whereMonth('created_at', now()->month)->count(),
                    'children_this_week' => Child::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                ],
                'vaccinations' => [
                    'total_vaccinations' => Vaccination::count(),
                    'vaccinations_this_month' => Vaccination::whereMonth('created_at', now()->month)->count(),
                    'pending_vaccinations' => Child::whereDoesntHave('vaccinations')->count(),
                ],
                'growth_monitoring' => [
                    'total_assessments' => GrowthRecords::count(),
                    'assessments_this_month' => GrowthRecords::whereMonth('created_at', now()->month)->count(),
                    'children_monitored' => GrowthRecords::distinct('child_id')->count(),
                ],
                'providers' => [
                    'total_providers' => HealthCareProvider::count(),
                    'active_providers' => HealthCareProvider::where('status', true)->count(),
                    'doctors' => HealthCareProvider::where('userRole', 'doctor')->count(),
                    'nurses' => HealthCareProvider::where('userRole', 'nurse')->count(),
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch system statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report data as CSV
     */
    public function exportReport(Request $request)
    {
        try {
            $reportType = $request->query('type', 'children');

            switch ($reportType) {
                case 'healthcare_providers':
                    $data = $this->getHealthcareProvidersReport()->getData()->data;
                    $filename = 'healthcare_providers_report.csv';
                    break;
                case 'children':
                    $data = $this->getChildrenReport()->getData()->data;
                    $filename = 'children_report.csv';
                    break;
                case 'growth_records':
                    $data = $this->getGrowthRecordsReport()->getData()->data;
                    $filename = 'growth_records_report.csv';
                    break;
                case 'vaccinations':
                    $data = $this->getVaccinationReport()->getData()->data;
                    $filename = 'vaccination_report.csv';
                    break;
                case 'activity_logs':
                    $data = $this->getActivityLogsReport()->getData()->data;
                    $filename = 'activity_logs_report.csv';
                    break;
                default:
                    return response()->json(['error' => 'Invalid report type'], 400);
            }

            // Convert data to CSV format
            $csvData = $this->convertToCSV($data);

            return response($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert data array to CSV format
     */
    private function convertToCSV($data)
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Write header
        fputcsv($output, array_keys((array)$data[0]));

        // Write data
        foreach ($data as $row) {
            fputcsv($output, (array)$row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
