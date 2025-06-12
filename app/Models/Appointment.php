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
        'appointment_date' => 'date',
        'notified_parent_5_days' => 'boolean',
        'notified_parent_1_day' => 'boolean',
        'notified_provider' => 'boolean',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
}
