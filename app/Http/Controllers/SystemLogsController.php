<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SystemLogsController extends Controller
{
    /**
     * Get all system logs with filtering and pagination
     */
    public function index(Request $request)
    {
        try {
            $query = ActivityLog::with('user')->latest();

            // Apply search filter
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Apply status filter
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            // Apply type filter
            if ($request->filled('type')) {
                $query->byType($request->type);
            }

            // Apply date range filter
            if ($request->filled('date_range')) {
                $query->byDateRange($request->date_range);
            }

            $perPage = $request->get('per_page', 20);
            $logs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'has_more_pages' => $logs->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load system logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load system logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system statistics
     */
    public function stats()
    {
        try {
            $stats = ActivityLog::getStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load system stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load system statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export logs to CSV
     */
    public function export(Request $request)
    {
        try {
            $query = ActivityLog::with('user')->latest();

            // Apply same filters as index
            if ($request->filled('search')) {
                $query->search($request->search);
            }
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }
            if ($request->filled('type')) {
                $query->byType($request->type);
            }
            if ($request->filled('date_range')) {
                $query->byDateRange($request->date_range);
            }

            $logs = $query->get();

            $csvData = [];
            $csvData[] = ['ID', 'User', 'Action', 'Details', 'IP Address', 'Timestamp', 'Status', 'Type'];

            foreach ($logs as $log) {
                $csvData[] = [
                    $log->id,
                    $log->user_name,
                    $log->action,
                    $log->details ?? '',
                    $log->ip_address ?? '',
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->status,
                    $log->type,
                ];
            }

            $filename = 'system_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';
            $filePath = storage_path('app/exports/' . $filename);

            // Create exports directory if it doesn't exist
            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            $file = fopen($filePath, 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);

            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to export system logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to export system logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all logs (admin only)
     */
    public function clear(Request $request)
    {
        try {
            // Get the authenticated user
            $user = $request->user();

            // Check if user has admin role
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            // Log the clear action before clearing
            ActivityLog::create([
                'user_id' => $user->id,
                'role' => $user->getRoleNames()->first() ?? '',
                'user_name' => $user->name,
                'action' => 'Clear System Logs',
                'details' => 'All system logs have been cleared',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'warning',
                'type' => 'delete',
            ]);

            // Clear all logs except the one we just created
            $latestLogId = ActivityLog::latest()->first()->id;
            ActivityLog::where('id', '!=', $latestLogId)->delete();

            return response()->json([
                'success' => true,
                'message' => 'All system logs have been cleared successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear system logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear system logs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Log a new activity (internal use)
     */
    public static function logActivity($user, $action, $details = null, $status = 'success', $type = 'info', $request = null)
    {
        try {
            ActivityLog::create([
                'user_id' => $user->id,
                'role' => $user->getRoleNames()->first() ?? '',
                'user_name' => $user->name,
                'action' => $action,
                'details' => $details,
                'ip_address' => $request ? $request->ip() : request()->ip(),
                'user_agent' => $request ? $request->userAgent() : request()->userAgent(),
                'status' => $status,
                'type' => $type,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log activity: ' . $e->getMessage());
        }
    }
}
