<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstanceMove extends Model
{
    use HasFactory;

    protected $fillable = [
        'monster_instance_id',
        'move_id',
        'slot',
    ];

    public function monsterInstance()
    {
        return $this->belongsTo(MonsterInstance::class, 'monster_instance_id');
    }

    public function move()
    {
        return $this->belongsTo(Move::class);
    }
}
