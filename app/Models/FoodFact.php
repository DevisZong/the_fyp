<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class FoodFact extends Model
{
    protected $fillable = [
        'age_group_start_month',
        'age_group_end_month',
        'message',
    ];

    /**
     * Get food facts for a specific age in months
     */
    public static function getForAge(int $ageInMonths): ?self
    {
        return self::where('age_group_start_month', '<=', $ageInMonths)
            ->where('age_group_end_month', '>=', $ageInMonths)
            ->first();
    }

    /**
     * Get food facts for a specific age range
     */
    public static function getForAgeRange(int $startMonth, int $endMonth)
    {
        return self::where(function ($query) use ($startMonth, $endMonth) {
            $query->whereBetween('age_group_start_month', [$startMonth, $endMonth])
                ->orWhereBetween('age_group_end_month', [$startMonth, $endMonth])
                ->orWhere(function ($q) use ($startMonth, $endMonth) {
                    $q->where('age_group_start_month', '<=', $startMonth)
                        ->where('age_group_end_month', '>=', $endMonth);
                });
        })->get();
    }

    /**
     * Get age group description
     */
    public function getAgeGroupAttribute(): string
    {
        if ($this->age_group_start_month == 0 && $this->age_group_end_month == 6) {
            return 'Tangu Kuzaliwa hadi Miezi 6';
        } elseif ($this->age_group_start_month == 6 && $this->age_group_end_month == 9) {
            return 'Miezi 6-9';
        } elseif ($this->age_group_start_month == 9 && $this->age_group_end_month == 12) {
            return 'Miezi 9-12';
        } elseif ($this->age_group_start_month == 12 && $this->age_group_end_month == 24) {
            return 'Mwaka 1 hadi Miaka 2';
        } elseif ($this->age_group_start_month == 24 && $this->age_group_end_month == 60) {
            return 'Miaka 2 hadi Miaka 5';
        } elseif ($this->age_group_start_month == 0 && $this->age_group_end_month == 60) {
            return 'Wakati wa Ugonjwa (Umri wote)';
        } elseif ($this->age_group_start_month == 6 && $this->age_group_end_month == 60) {
            return 'Usafi na Usalama (Miezi 6+)';
        }

        return "Miezi {$this->age_group_start_month} hadi {$this->age_group_end_month}";
    }
}
