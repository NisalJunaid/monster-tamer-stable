<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Move extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type_id',
        'category',
        'power',
        'accuracy',
        'pp',
        'priority',
        'effect_json',
    ];

    protected $casts = [
        'effect_json' => 'array',
    ];

    public function type()
    {
        return $this->belongsTo(Type::class);
    }
}
