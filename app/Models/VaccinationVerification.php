<?php
// app/Models/VaccinationVerification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VaccinationVerification extends Model
{
    protected $fillable = [
        'child_id',
        'health_care_provider_id',
        'vaccination_code',
        'vaccination_no',
        'verification_code',
        'expires_at',
    ];

    protected $dates = ['expires_at'];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function healthCareProvider()
    {
        return $this->belongsTo(HealthCareProvider::class);
    }
}
