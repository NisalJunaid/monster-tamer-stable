<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonsterSpecies extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'primary_type_id',
        'secondary_type_id',
        'capture_rate',
        'rarity_tier',
        'base_experience',
    ];

    public function primaryType()
    {
        return $this->belongsTo(Type::class, 'primary_type_id');
    }

    public function secondaryType()
    {
        return $this->belongsTo(Type::class, 'secondary_type_id');
    }

    public function stages()
    {
        return $this->hasMany(MonsterSpeciesStage::class, 'species_id');
    }

    public function learnset()
    {
        return $this->hasMany(SpeciesLearnset::class, 'species_id');
    }
}
