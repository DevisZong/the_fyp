<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Appointment;
use App\Models\Vaccination;
use App\Models\User;
use App\Models\HealthCareProvider;
use App\Models\ActivityLog;
use App\Models\GrowthRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Get comprehensive admin dashboard statistics
     */
    public function getStats()
    {
        try {
            // Basic counts
            $totalUsers = User::count();
            $totalChildren = Child::count();
            $totalHealthcareProviders = HealthCareProvider::count();
            $totalParents = User::role('child')->count(); // Parents are users with child role
            $childrenEnrolled = $totalChildren;

            // Appointments
            $totalAppointments = Appointment::count();
            $todayAppointments = Appointment::whereDate('appointment_date', Carbon::today())->count();
            $upcomingAppointments = Appointment::where('appointment_date', '>', Carbon::now())->count();

            // Calculate trends (compared to last month)
            $lastMonthUsers = User::whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->count();
            $currentMonthUsers = User::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();

            $lastMonthChildren = Child::whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->count();
            $currentMonthChildren = Child::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();

            $lastMonthParents = User::role('child')
                ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->count();
            $currentMonthParents = User::role('child')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();

            // Calculate percentage changes
            $userTrend = $lastMonthUsers > 0 ?
                round((($currentMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100, 1) : ($currentMonthUsers > 0 ? 100 : 0);
            $childrenTrend = $lastMonthChildren > 0 ?
                round((($currentMonthChildren - $lastMonthChildren) / $lastMonthChildren) * 100, 1) : ($currentMonthChildren > 0 ? 100 : 0);
            $parentsTrend = $lastMonthParents > 0 ?
                round((($currentMonthParents - $lastMonthParents) / $lastMonthParents) * 100, 1) : ($currentMonthParents > 0 ? 100 : 0);

            return response()->json([
                'success' => true,
                'data' => [
                    'totalUsers' => $totalUsers,
                    'totalChildren' => $totalChildren,
                    'totalAppointments' => $totalAppointments,
                    'totalHealthcareProviders' => $totalHealthcareProviders,
                    'totalParents' => $totalParents,
                    'childrenEnrolled' => $childrenEnrolled,
                    'todayAppointments' => $todayAppointments,
                    'upcomingAppointments' => $upcomingAppointments,
                    'trends' => [
                        'users' => $userTrend,
                        'children' => $childrenTrend,
                        'parents' => $parentsTrend,
                        'appointments' => 0 // No change for now
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent activity for admin dashboard
     */
    public function getRecentActivity()
    {
        try {
            $recentActivities = ActivityLog::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'user_name' => $activity->user_name,
                        'action' => $activity->action,
                        'details' => $activity->details,
                        'type' => $activity->type,
                        'status' => $activity->status,
                        'created_at' => $activity->created_at->diffForHumans(),
                        'icon' => $this->getActivityIcon($activity->type)
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $recentActivities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notifications for admin dashboard
     */
    public function getNotifications()
    {
        try {
            $notifications = [];

            // Check for overdue vaccinations (children over 2 months without BCG)
            $overdueVaccinations = Child::whereDoesntHave('vaccinations', function ($query) {
                $query->where('vaccination_code', 'BCG')
                    ->where('Hali', 'imekamilika');
            })
                ->where('created_at', '<', Carbon::now()->subMonths(2))
                ->count();

            if ($overdueVaccinations > 0) {
                $notifications[] = [
                    'type' => 'warning',
                    'icon' => 'fas fa-exclamation-triangle',
                    'message' => "{$overdueVaccinations} children overdue for BCG vaccination.",
                    'action' => 'View Vaccinations'
                ];
            }

            // Check for upcoming appointments (next 7 days)
            $upcomingAppointments = Appointment::whereBetween('appointment_date', [
                Carbon::now(),
                Carbon::now()->addDays(7)
            ])->with('child')->get();

            foreach ($upcomingAppointments->take(3) as $appointment) {
                $notifications[] = [
                    'type' => 'info',
                    'icon' => 'fas fa-info-circle',
                    'message' => "Upcoming appointment: {$appointment->child->childName} on " .
                        Carbon::parse($appointment->appointment_date)->format('d/m/Y'),
                    'action' => 'View Appointments'
                ];
            }

            // Check for new registrations today
            $newRegistrationsToday = Child::whereDate('created_at', Carbon::today())->count();
            if ($newRegistrationsToday > 0) {
                $notifications[] = [
                    'type' => 'success',
                    'icon' => 'fas fa-check-circle',
                    'message' => "{$newRegistrationsToday} new children registered today.",
                    'action' => 'View Children'
                ];
            }

            // Check for new healthcare provider registrations this week
            $newProvidersThisWeek = HealthCareProvider::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count();

            if ($newProvidersThisWeek > 0) {
                $notifications[] = [
                    'type' => 'success',
                    'icon' => 'fas fa-check-circle',
                    'message' => "{$newProvidersThisWeek} new healthcare provider(s) registered this week.",
                    'action' => 'View Providers'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vaccination trends data for charts
     */
    public function getVaccinationTrends()
    {
        try {
            // Get vaccination data for the last 6 months
            $trends = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $count = Vaccination::whereMonth('updated_at', $date->month)
                    ->whereYear('updated_at', $date->year)
                    ->where('Hali', 'imekamilika')
                    ->count();

                $trends[] = [
                    'month' => $date->format('M Y'),
                    'count' => $count
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $trends
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vaccination trends',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system progress data
     */
    public function getSystemProgress()
    {
        try {
            $totalChildren = Child::count();

            // Calculate vaccination coverage (children with at least one completed vaccination)
            $vaccinatedChildren = Child::whereHas('vaccinations', function ($query) {
                $query->where('Hali', 'imekamilika');
            })->count();

            $vaccinationCoverage = $totalChildren > 0 ?
                round(($vaccinatedChildren / $totalChildren) * 100, 1) : 0;

            // Calculate enrollment progress (assume target of 200 children)
            $enrollmentTarget = 200;
            $enrollmentProgress = $enrollmentTarget > 0 ?
                round(($totalChildren / $enrollmentTarget) * 100, 1) : 0;

            // Cap at 100%
            $enrollmentProgress = min($enrollmentProgress, 100);

            return response()->json([
                'success' => true,
                'data' => [
                    'vaccination_coverage' => $vaccinationCoverage,
                    'enrollment_progress' => $enrollmentProgress
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch system progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all admin dashboard data in one call
     */
    public function getDashboardData()
    {
        try {
            $stats = $this->getStats();
            $activity = $this->getRecentActivity();
            $notifications = $this->getNotifications();
            $trends = $this->getVaccinationTrends();
            $progress = $this->getSystemProgress();

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats->getData()->data,
                    'recent_activity' => $activity->getData()->data,
                    'notifications' => $notifications->getData()->data,
                    'vaccination_trends' => $trends->getData()->data,
                    'system_progress' => $progress->getData()->data
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly registration trends for charts
     */
    public function getRegistrationTrends()
    {
        try {
            $currentYear = Carbon::now()->year;

            $monthlyData = Child::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
                ->whereYear('created_at', $currentYear)
                ->groupBy(DB::raw('MONTH(created_at)'))
                ->orderBy('month')
                ->get();

            $trends = [];
            for ($i = 1; $i <= 12; $i++) {
                $count = $monthlyData->where('month', $i)->first()?->count ?? 0;
                $trends[] = [
                    'month' => Carbon::createFromDate($currentYear, $i, 1)->format('M'),
                    'count' => $count
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $trends
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch registration trends',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user growth trends (all users: admins, healthcare providers, parents)
     */
    public function getUserGrowthTrends()
    {
        try {
            $currentYear = Carbon::now()->year;

            $monthlyData = User::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
                ->whereYear('created_at', $currentYear)
                ->groupBy(DB::raw('MONTH(created_at)'))
                ->orderBy('month')
                ->get();

            $trends = [];
            for ($i = 1; $i <= 12; $i++) {
                $count = $monthlyData->where('month', $i)->first()?->count ?? 0;
                $trends[] = [
                    'month' => Carbon::createFromDate($currentYear, $i, 1)->format('M'),
                    'count' => $count
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $trends
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user growth trends',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system overview with key metrics
     */
    public function getSystemOverview()
    {
        try {
            $overview = [
                'total_users' => User::count(),
                'total_children' => Child::count(),
                'total_healthcare_providers' => HealthCareProvider::count(),
                'total_vaccinations_completed' => Vaccination::where('Hali', 'imekamilika')->count(),
                'total_growth_records' => GrowthRecords::count(),
                'total_appointments' => Appointment::count(),
                'active_today' => ActivityLog::whereDate('created_at', Carbon::today())->count(),
                'system_uptime' => '99.9%', // Static for now
            ];

            return response()->json([
                'success' => true,
                'data' => $overview
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch system overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get icon for activity types
     */
    private function getActivityIcon($type)
    {
        $icons = [
            'login' => 'fas fa-sign-in-alt',
            'logout' => 'fas fa-sign-out-alt',
            'create' => 'fas fa-plus-circle',
            'update' => 'fas fa-edit',
            'delete' => 'fas fa-trash',
            'info' => 'fas fa-info-circle',
            'error' => 'fas fa-exclamation-circle'
        ];

        return $icons[$type] ?? 'fas fa-circle';
    }

    /**
     * Get upcoming appointments for admin dashboard
     */
    public function getUpcomingAppointments()
    {
        try {
            $upcomingAppointments = Appointment::with(['child'])
                ->where('appointment_date', '>=', Carbon::now())
                ->orderBy('appointment_date', 'asc')
                ->limit(10)
                ->get()
                ->map(function ($appointment) {
                    return [
                        'id' => $appointment->id,
                        'child_name' => $appointment->child->childName ?? 'Unknown',
                        'appointment_date' => $appointment->appointment_date,
                        'formatted_date' => Carbon::parse($appointment->appointment_date)->format('d/m/Y'),
                        'formatted_time' => Carbon::parse($appointment->appointment_date)->format('H:i'),
                        'days_until' => Carbon::parse($appointment->appointment_date)->diffInDays(Carbon::now()),
                        'type' => $appointment->appointment_type ?? 'General Checkup'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $upcomingAppointments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch upcoming appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real system information
     */
    public function getSystemInfo()
    {
        try {
            // Get real Laravel version
            $laravelVersion = app()->version();

            // Get real PHP version
            $phpVersion = PHP_VERSION;

            // Get environment
            $environment = app()->environment();

            // Get timezone
            $timezone = config('app.timezone');

            // Calculate estimated database size (simple approach)
            $totalRecords = User::count() + Child::count() + HealthCareProvider::count() +
                Appointment::count() + Vaccination::count() + ActivityLog::count();

            // Rough estimate: each record averages ~1KB
            $estimatedSizeKB = $totalRecords * 1;
            $estimatedSizeMB = round($estimatedSizeKB / 1024, 1);
            if ($estimatedSizeMB < 1) $estimatedSizeMB = 1; // Minimum 1MB

            // SSL status (check if app is running on HTTPS)
            $sslActive = request()->isSecure();

            // Server uptime (simple check - if we can respond, we're online)
            $systemStatus = 'Online';

            // Last activity as indicator of system activity
            $lastActivity = ActivityLog::latest()->first();
            $lastSystemActivity = $lastActivity ? $lastActivity->created_at->diffForHumans() : 'No recent activity';
            $systemInfo = [
                'version' => 'CVGMS v2.1.0',
                'laravel_version' => $laravelVersion,
                'php_version' => $phpVersion,
                'environment' => ucfirst($environment),
                'timezone' => $timezone,
                'database_size_mb' => $estimatedSizeMB,
                'total_records' => $totalRecords,
                'ssl_active' => $sslActive,
                'system_status' => $systemStatus,
                'last_activity' => $lastSystemActivity,
                'server_time' => Carbon::now()->format('Y-m-d H:i:s T')
            ];

            return response()->json([
                'success' => true,
                'data' => $systemInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch system information',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
