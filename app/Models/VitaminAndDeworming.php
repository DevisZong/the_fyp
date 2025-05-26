<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VitaminAndDeworming extends Model
{
    protected $fillable = [
        'child_id',
        'Vitamin_A',
        'Deworming',
        'status'
    ];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }
}
