<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VaccinationReminder extends Model
{
    protected $fillable = [
        'child_id',
        'vaccine_age',
        'sent_at',
    ];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }
}
