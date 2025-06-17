<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VitaminDewormingReminder extends Model
{
    protected $fillable = [
        'child_id',
        'visit_month',
        'reminder_type',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }
}
