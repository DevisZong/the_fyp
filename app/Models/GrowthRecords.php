<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrowthRecords extends Model
{
    // protected $table = 'growth_records';

    protected $fillable = [
        'child_id',
        'weight',
        'height',
        'health_care_provider_id'
    ];

    public function child()
    {
        return $this->belongsTo(Child::class, 'child_id');
    }
    public function HealthCareProvider()
    {
        return $this->belongsTo(HealthCareProvider::class, 'health_care_provider_id');
    }
}
