<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogController extends Controller
{
    // Fetch activity logs for frontend
    public function index()
    {
        $logs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->get(['created_at as timestamp', 'role', 'user_name', 'action', 'status']);
        return response()->json($logs);
    }
}
