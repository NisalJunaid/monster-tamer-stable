<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoneSpawnEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id',
        'species_id',
        'weight',
        'min_level',
        'max_level',
        'rarity_tier',
        'conditions_json',
    ];

    protected $casts = [
        'conditions_json' => 'array',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function species()
    {
        return $this->belongsTo(MonsterSpecies::class, 'species_id');
    }
}
