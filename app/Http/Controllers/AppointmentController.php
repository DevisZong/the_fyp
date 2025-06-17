<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    /**
     * Get appointments for a child with dates and names.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAppointments(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get the child based on the authenticated user
        $child = \App\Models\Child::where('user_id', $user->id)->first();

        if (!$child) {
            return response()->json(['error' => 'No child associated with this user'], 404);
        }

        // Debugging logs
        Log::info('Authenticated user ID: ' . $user->id);
        Log::info('Found child ID: ' . ($child ? $child->id : 'null'));

        $appointments = Appointment::where('child_id', $child->id)
            ->orderBy('appointment_date')
            ->get(['appointment_date', 'appointment_name']);

        Log::info('Appointments found: ' . $appointments->count());

        return response()->json(['appointments' => $appointments]);
    }

    /**
     * Trigger sending appointment notifications manually.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendNotifications()
    {
        // Run the artisan command to send notifications
        Artisan::call('appointments:send-notifications');

        return response()->json(['message' => 'Appointment notifications sent successfully.']);
    }

    /**
     * Get appointments for dashboard with calendar data
     */
    public function getDashboardAppointments(Request $request)
    {
        try {
            $currentMonth = $request->get('month', now()->month);
            $currentYear = $request->get('year', now()->year);

            $appointments = Appointment::with('child')
                ->whereMonth('appointment_date', $currentMonth)
                ->whereYear('appointment_date', $currentYear)
                ->orderBy('appointment_date')
                ->get();
            $formattedAppointments = $appointments->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'child' => $appointment->child->childName,
                    'date' => $appointment->appointment_date->format('Y-m-d'),
                    'time' => $appointment->appointment_time, // Use the random working hours
                    'type' => $appointment->appointment_name,
                    'appointment_type' => $appointment->appointment_type
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedAppointments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's appointments for dashboard
     */
    public function getTodayAppointments()
    {
        try {
            $today = now()->format('Y-m-d');

            $appointments = Appointment::with('child')
                ->whereDate('appointment_date', $today)
                ->orderBy('appointment_date')
                ->get();
            $formattedAppointments = $appointments->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'child' => $appointment->child->childName,
                    'time' => $appointment->appointment_time, // Use the random working hours
                    'type' => $appointment->appointment_name,
                    'appointment_type' => $appointment->appointment_type
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedAppointments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch today\'s appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
