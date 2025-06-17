<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogController extends Controller
{    // Fetch activity logs for frontend
    public function index()
    {
        try {
            $logs = ActivityLog::orderBy('created_at', 'desc')
                ->get(['id', 'created_at as timestamp', 'role', 'user_name', 'action', 'status']);

            return response()->json([
                'status' => 'success',
                'message' => 'Activity logs retrieved successfully',
                'data' => $logs,
                'total' => $logs->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve activity logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
