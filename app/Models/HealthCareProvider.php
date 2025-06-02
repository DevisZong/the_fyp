<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCareProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'license',
        'userRole',
        'facility',
        'contact',
        'gender',
        'status',
        'user_id',
    ];
    protected $casts = [
        'status' => 'boolean',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function children()
    {
        return $this->belongsToMany(Child::class, 'child_health_care_provider');
    }

    public function growthRecords()
    {
        return $this->hasMany(GrowthRecords::class, 'health_care_provider_id');
    }

    public function vaccinations()
    {
        return $this->hasMany(Vaccination::class,  'health_care_provider_id');
    }
}
