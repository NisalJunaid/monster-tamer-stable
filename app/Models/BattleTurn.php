<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BattleTurn extends Model
{
    use HasFactory;

    protected $fillable = [
        'battle_id',
        'turn_number',
        'actor_user_id',
        'action_json',
        'result_json',
    ];

    protected $casts = [
        'action_json' => 'array',
        'result_json' => 'array',
    ];

    public function battle(): BelongsTo
    {
        return $this->belongsTo(Battle::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
