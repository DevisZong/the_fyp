<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthCareProvider extends Model
{
    protected $fillable = [
        'name',
        'license',
        'userRole',
        'facility',
        'contact',
        'gender',
        'status'
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
        return $this->hasMany(Child::class);
    }
    public function growthRecords()
    {
        return $this->hasMany(GrowthRecords::class);
    }
    public function vaccinations()
    {
        return $this->hasMany(Vaccination::class);
    }
}
