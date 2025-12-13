<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'key','name','description','category','is_consumable','stack_limit','effect_type','effect_payload'
    ];

    protected $casts = [
        'is_consumable' => 'boolean',
        'effect_payload' => 'array',
    ];
}
