<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vaccination extends Model
{
    protected $fillable = ['child_id', 'vaccination_code','vaccination_no', 'status', 'health_care_provider_id'];
    
    protected $casts = ['status' => 'boolean'];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }
    public function healthCareProvider()
    {
        return $this->belongsTo(HealthCareProvider::class, 'health_care_provider_id');
    }
}