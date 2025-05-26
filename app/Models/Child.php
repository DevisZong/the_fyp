<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Vaccination;
use Database\Seeders\VaccinationSeeder;

class Child extends Model
{

    protected $fillable = [
        'childNo',
        'childName',
        'date_of_birth',
        'gender',
        'birthWeight',
        'fatherName',
        'motherName',
        'birthFacility',
        'birthAttendant',
        'email',
        'phoneNo',
        'address',
        'motherAge',
        'health_care_provider_id'
    ];

    protected $casts = [
        'dateOfBirth' => 'datetime',
        'address' => 'array',
    ];

    public function growthRecords(){
        return $this->hasMany(GrowthRecords::class, 'child_id');
    }

    public function latestGrowthRecord(){
        return $this->growthRecords()->latest('created_at')->first();
    }


    public function user(){
        return $this->hasOne(User::class, 'child_id', 'id');
    }

    public function vaccination() {
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


    public function healthCareProvider()
    {
        return $this->belongsTo(HealthCareProvider::class, 'health_care_provider_id');
    }
}
