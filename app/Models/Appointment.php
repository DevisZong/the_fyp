<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'child_id',
        'appointment_type', // 'monthly_visit' or 'vaccination'
        'appointment_date',
        'appointment_name', // e.g. 'Monthly Visit', 'bOPV-1', etc.
        'notified_parent_5_days',
        'notified_parent_1_day',
        'notified_provider',
    ];
    protected $casts = [
        'appointment_date' => 'datetime',
        'notified_parent_5_days' => 'boolean',
        'notified_parent_1_day' => 'boolean',
        'notified_provider' => 'boolean',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * Generate a random appointment time during working hours (8:00 AM - 5:00 PM)
     */
    public function getRandomAppointmentTime()
    {
        // Working hours: 8:00 AM to 5:00 PM (08:00 to 17:00)
        $workingHours = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00'];

        return $workingHours[array_rand($workingHours)];
    }

    /**
     * Get appointment time (random working hours based on ID for consistency)
     */
    public function getAppointmentTimeAttribute()
    {
        // Use appointment ID to ensure consistent time for same appointment
        $workingHours = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00'];

        $index = $this->id % count($workingHours);
        return $workingHours[$index];
    }

    /**
     * Get formatted appointment time with AM/PM
     */
    public function getFormattedTimeAttribute()
    {
        $time = $this->appointment_time;
        return date('g:i A', strtotime($time));
    }
}
