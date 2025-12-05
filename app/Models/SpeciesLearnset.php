<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpeciesLearnset extends Model
{
    use HasFactory;

    protected $fillable = [
        'species_id',
        'stage_number',
        'move_id',
        'learn_level',
        'learn_method',
    ];

    public function species()
    {
        return $this->belongsTo(MonsterSpecies::class, 'species_id');
    }

    public function move()
    {
        return $this->belongsTo(Move::class, 'move_id');
    }
}
