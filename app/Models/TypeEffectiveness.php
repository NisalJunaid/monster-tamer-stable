<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeEffectiveness extends Model
{
    use HasFactory;

    protected $fillable = [
        'attack_type_id',
        'defend_type_id',
        'multiplier',
    ];

    protected $casts = [
        'multiplier' => 'float',
    ];

    public function attackType()
    {
        return $this->belongsTo(Type::class, 'attack_type_id');
    }

    public function defendType()
    {
        return $this->belongsTo(Type::class, 'defend_type_id');
    }
}
