<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Battle extends Model
{
    use HasFactory;

    protected $fillable = [
        'seed',
        'status',
        'player1_id',
        'player2_id',
        'started_at',
        'ended_at',
        'winner_user_id',
        'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function turns(): HasMany
    {
        return $this->hasMany(BattleTurn::class);
    }
}
