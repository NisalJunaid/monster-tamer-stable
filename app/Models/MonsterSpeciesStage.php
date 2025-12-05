<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonsterSpeciesStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'species_id',
        'stage_number',
        'name',
        'hp',
        'attack',
        'defense',
        'sp_attack',
        'sp_defense',
        'speed',
        'evolves_to_stage_id',
        'evolve_trigger_json',
    ];

    protected $casts = [
        'evolve_trigger_json' => 'array',
    ];

    public function species()
    {
        return $this->belongsTo(MonsterSpecies::class, 'species_id');
    }

    public function evolvesTo()
    {
        return $this->belongsTo(self::class, 'evolves_to_stage_id');
    }
}
