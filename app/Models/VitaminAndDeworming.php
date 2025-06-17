<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class VitaminAndDeworming extends Model
{
    protected $fillable = [
        'child_id',
        'Vitamin_A',
        'Deworming',
        'status'
    ];

    protected $casts = [
        'status' => 'string',
    ];

    protected $dates = ['visit_date'];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * Get all scheduled visit dates for a child
     */
    public static function getScheduledVisits($child)
    {
        $dob = Carbon::parse($child->date_of_birth);
        $visits = [];

        // Schedule 10 visits, 6 months apart until 5 years
        for ($i = 0; $i < 10; $i++) {
            $visits[] = $dob->copy()->addMonths(6 * ($i + 1));
        }

        return $visits;
    }

    /**
     * Check if the visit is missed
     */
    public function checkIfMissed()
    {
        $child = $this->child;
        $dob = Carbon::parse($child->date_of_birth);
        $now = Carbon::now();
        $ageInMonths = $dob->diffInMonths($now);

        // Get the visit number based on child's age
        $visitNumber = floor($ageInMonths / 6);

        // If child has missed previous visits
        if ($visitNumber > 0 && !$this->Vitamin_A && !$this->Deworming) {
            $this->status = 'amekosa';
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Get missed visits count
     */
    public static function getMissedVisits($child)
    {
        $dob = Carbon::parse($child->date_of_birth);
        $now = Carbon::now();
        $ageInMonths = $dob->diffInMonths($now);

        // Calculate how many visits should have happened by now
        $expectedVisits = floor($ageInMonths / 6);

        // Get actual completed or missed visits (exclude future visits)
        $actualVisits = self::where('child_id', $child->id)
            ->where('created_at', '<=', $now)
            ->count();

        return max(0, min(10, $expectedVisits) - $actualVisits);
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($vitaminAndDeworming) {
            // Skip automatic status changes if this is a new record being created
            // (let the creation logic handle the initial status)
            if (!$vitaminAndDeworming->exists) {
                return;
            }

            $scheduledDate = Carbon::parse($vitaminAndDeworming->created_at);
            $now = Carbon::now();

            // If both vitamins were given, status should be imekamilika
            if ($vitaminAndDeworming->Vitamin_A && $vitaminAndDeworming->Deworming) {
                $vitaminAndDeworming->status = 'imekamilika';
            }
            // If scheduled date is past and vitamins not given, mark as missed
            else if ($scheduledDate->lt($now) && (!$vitaminAndDeworming->Vitamin_A && !$vitaminAndDeworming->Deworming)) {
                $vitaminAndDeworming->status = 'amekosa';
            }
            // For future visits, status should be inasubiri
            else if ($scheduledDate->gt($now)) {
                $vitaminAndDeworming->status = 'inasubiri';
            }
        });
    }
}
