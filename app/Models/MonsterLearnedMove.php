<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonsterLearnedMove extends Model
{
    protected $fillable = [
        'player_monster_id',
        'move_id',
        'learned_method',
    ];

    public function monster()
    {
        return $this->belongsTo(PlayerMonster::class, 'player_monster_id');
    }

    public function move()
    {
        return $this->belongsTo(Move::class);
    }
}
