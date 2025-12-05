<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonsterInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'species_id',
        'current_stage_id',
        'nickname',
        'level',
        'experience',
        'nature',
        'iv_json',
        'ev_json',
    ];

    protected $casts = [
        'iv_json' => 'array',
        'ev_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function species()
    {
        return $this->belongsTo(MonsterSpecies::class, 'species_id');
    }

    public function currentStage()
    {
        return $this->belongsTo(MonsterSpeciesStage::class, 'current_stage_id');
    }

    public function moves()
    {
        return $this->hasMany(InstanceMove::class, 'monster_instance_id');
    }
}
