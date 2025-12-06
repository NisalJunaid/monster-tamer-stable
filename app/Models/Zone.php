<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'priority',
        'is_active',
        'shape_type',
        'geom',
        'center',
        'radius_m',
        'min_lat',
        'max_lat',
        'min_lng',
        'max_lng',
        'rules_json',
        'spawn_strategy',
        'spawn_rules',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rules_json' => 'array',
        'spawn_rules' => 'array',
    ];

    public function spawnEntries()
    {
        return $this->hasMany(ZoneSpawnEntry::class);
    }
}
