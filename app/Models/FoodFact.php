<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodFact extends Model
{
    protected $fillable = [
        'age_group_start_month',
        'age_group_end_month',
        'message',
    ];
}
