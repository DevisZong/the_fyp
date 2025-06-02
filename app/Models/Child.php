<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Vaccination;
use Database\Seeders\VaccinationSeeder;

class Child extends Model
{
    use HasFactory;

    protected $fillable = [
        'childNo',
        'user_id',
        'childName',
        'date_of_birth',
        'gender',
        'birthWeight',
        'birthHeight',
        'fatherName',
        'motherName',
        'birthFacility',
        'birthAttendant',
        'email',
        'phoneNo',
        'address',
        'motherAge'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'address' => 'array',
        'phoneNo' => 'integer',
    ];

    protected $appends = [
        'date_of_birth_formatted',
    ];

    public function getDateOfBirthFormattedAttribute()
    {
        return isset($this->attributes['date_of_birth'])
            ? \Carbon\Carbon::parse($this->attributes['date_of_birth'])->format('Y-m-d')
            : null;
    }

    public function growthRecords()
    {
        return $this->hasMany(GrowthRecords::class, 'child_id');
    }

    public function latestGrowthRecord()
    {
        return $this->hasOne(GrowthRecords::class, 'child_id')->latestOfMany('created_at');
    }



    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    public function vaccination()
    {
        return $this->hasMany(Vaccination::class, 'child_id', 'id');
    }

    protected static function booted()
    {
        static::created(function ($child) {
            foreach (VaccinationSeeder::getVaccinationCodes() as $code) {
                Vaccination::create([
                    'child_id' => $child->id,
                    'vaccination_code' => $code,
                    'vaccination_no' => null,
                    'status' => false
                ]);
            }
        });
    }

    public function vitaminAndDeworming()
    {
        return $this->hasMany(VitaminAndDeworming::class, 'child_id', 'id');
    }

    public function healthCareProviders()
    {
        return $this->belongsToMany(HealthCareProvider::class, 'child_health_care_provider');
    }

    public function vaccinationReminders()
    {
        return $this->hasMany(VaccinationReminder::class, 'child_id');
    }

    public function getAgeInWeeksAttribute()
    {
        return $this->date_of_birth->diffInWeeks(now());
    }

    public function getAgeInMonthsAttribute()
    {
        if (isset($this->attributes['age_in_months'])) {
            return $this->attributes['age_in_months'];
        }
        if (isset($this->date_of_birth)) {
            return $this->date_of_birth->diffInMonths(now());
        }
        if (isset($this->age_in_weeks)) {
            return floor($this->age_in_weeks / 4.345);
        }
        return null;
    }
}
