<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vaccination extends Model
{
    use HasFactory;

    protected $fillable = [
        'child_id',
        'vaccination_code',
        'vaccination_no',
        'Hali',
        'health_care_provider_id'
    ];

    protected $casts = [
        'Hali' => 'string',
    ];

     public function child()
    {
        return $this->belongsTo(Child::class);
    }
    public function healthCareProvider()
    {
        return $this->belongsTo(HealthCareProvider::class, 'health_care_provider_id');
    }
}
