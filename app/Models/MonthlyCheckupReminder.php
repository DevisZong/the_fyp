<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyCheckupReminder extends Model
{
    protected $fillable = [
        'child_id',
        'month',
        'sent_at',
    ];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }
}
