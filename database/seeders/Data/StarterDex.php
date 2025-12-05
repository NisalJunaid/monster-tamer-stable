<?php

namespace Database\Seeders\Data;

class StarterDex
{
    public static function species(): array
    {
        return [
            [
                'name' => 'Flameling',
                'primary_type' => 'Fire',
                'secondary_type' => null,
                'capture_rate' => 45,
                'rarity_tier' => 'starter',
                'base_experience' => 62,
                'stages' => [
                    ['stage' => 1, 'name' => 'Flameling', 'hp' => 39, 'attack' => 52, 'defense' => 43, 'sp_attack' => 60, 'sp_defense' => 50, 'speed' => 65, 'evolve' => ['level' => 16]],
                    ['stage' => 2, 'name' => 'Blazetail', 'hp' => 58, 'attack' => 64, 'defense' => 58, 'sp_attack' => 80, 'sp_defense' => 65, 'speed' => 80, 'evolve' => ['level' => 32]],
                    ['stage' => 3, 'name' => 'Pyreclaw', 'hp' => 78, 'attack' => 84, 'defense' => 78, 'sp_attack' => 109, 'sp_defense' => 85, 'speed' => 100, 'evolve' => null],
                ],
                'learnset' => [
                    ['move' => 'Ember', 'stage' => 1, 'method' => 'level-up', 'level' => 1],
                    ['move' => 'Flame Burst', 'stage' => 2, 'method' => 'level-up', 'level' => 16],
                    ['move' => 'Inferno', 'stage' => 3, 'method' => 'level-up', 'level' => 36],
                ],
            ],
            [
                'name' => 'Aquafin',
                'primary_type' => 'Water',
                'secondary_type' => null,
                'capture_rate' => 45,
                'rarity_tier' => 'starter',
                'base_experience' => 63,
                'stages' => [
                    ['stage' => 1, 'name' => 'Aquafin', 'hp' => 44, 'attack' => 48, 'defense' => 65, 'sp_attack' => 50, 'sp_defense' => 64, 'speed' => 43, 'evolve' => ['level' => 16]],
                    ['stage' => 2, 'name' => 'Maritide', 'hp' => 59, 'attack' => 63, 'defense' => 80, 'sp_attack' => 65, 'sp_defense' => 80, 'speed' => 58, 'evolve' => ['level' => 32]],
                    ['stage' => 3, 'name' => 'Tidalord', 'hp' => 79, 'attack' => 83, 'defense' => 100, 'sp_attack' => 85, 'sp_defense' => 105, 'speed' => 78, 'evolve' => null],
                ],
                'learnset' => [
                    ['move' => 'Water Jet', 'stage' => 1, 'method' => 'level-up', 'level' => 1],
                    ['move' => 'Bubble Beam', 'stage' => 2, 'method' => 'level-up', 'level' => 16],
                    ['move' => 'Tsunami Crash', 'stage' => 3, 'method' => 'level-up', 'level' => 40],
                ],
            ],
            [
                'name' => 'Spriglet',
                'primary_type' => 'Nature',
                'secondary_type' => 'Wind',
                'capture_rate' => 45,
                'rarity_tier' => 'starter',
                'base_experience' => 64,
                'stages' => [
                    ['stage' => 1, 'name' => 'Spriglet', 'hp' => 45, 'attack' => 49, 'defense' => 49, 'sp_attack' => 65, 'sp_defense' => 65, 'speed' => 45, 'evolve' => ['level' => 16]],
                    ['stage' => 2, 'name' => 'Galeleaf', 'hp' => 60, 'attack' => 62, 'defense' => 63, 'sp_attack' => 80, 'sp_defense' => 80, 'speed' => 60, 'evolve' => ['level' => 32]],
                    ['stage' => 3, 'name' => 'Tempestree', 'hp' => 80, 'attack' => 82, 'defense' => 83, 'sp_attack' => 100, 'sp_defense' => 100, 'speed' => 80, 'evolve' => null],
                ],
                'learnset' => [
                    ['move' => 'Leaf Blade', 'stage' => 1, 'method' => 'level-up', 'level' => 1],
                    ['move' => 'Gust', 'stage' => 1, 'method' => 'level-up', 'level' => 7],
                    ['move' => 'Hurricane Bloom', 'stage' => 3, 'method' => 'level-up', 'level' => 40],
                ],
            ],
            [
                'name' => 'Voltkit',
                'primary_type' => 'Electric',
                'secondary_type' => 'Metal',
                'capture_rate' => 45,
                'rarity_tier' => 'starter',
                'base_experience' => 65,
                'stages' => [
                    ['stage' => 1, 'name' => 'Voltkit', 'hp' => 35, 'attack' => 55, 'defense' => 40, 'sp_attack' => 50, 'sp_defense' => 50, 'speed' => 90, 'evolve' => ['level' => 18]],
                    ['stage' => 2, 'name' => 'Cymet', 'hp' => 55, 'attack' => 65, 'defense' => 55, 'sp_attack' => 75, 'sp_defense' => 65, 'speed' => 105, 'evolve' => ['level' => 34]],
                    ['stage' => 3, 'name' => 'Voltitan', 'hp' => 75, 'attack' => 85, 'defense' => 70, 'sp_attack' => 110, 'sp_defense' => 90, 'speed' => 130, 'evolve' => null],
                ],
                'learnset' => [
                    ['move' => 'Thunder Jolt', 'stage' => 1, 'method' => 'level-up', 'level' => 1],
                    ['move' => 'Metal Claw', 'stage' => 1, 'method' => 'level-up', 'level' => 10],
                    ['move' => 'Gigawatt Surge', 'stage' => 3, 'method' => 'level-up', 'level' => 44],
                ],
            ],
            [
                'name' => 'Terrapup',
                'primary_type' => 'Earth',
                'secondary_type' => 'Toxic',
                'capture_rate' => 45,
                'rarity_tier' => 'starter',
                'base_experience' => 66,
                'stages' => [
                    ['stage' => 1, 'name' => 'Terrapup', 'hp' => 50, 'attack' => 70, 'defense' => 70, 'sp_attack' => 35, 'sp_defense' => 35, 'speed' => 30, 'evolve' => ['level' => 16]],
                    ['stage' => 2, 'name' => 'Venomound', 'hp' => 70, 'attack' => 85, 'defense' => 85, 'sp_attack' => 45, 'sp_defense' => 55, 'speed' => 40, 'evolve' => ['level' => 36]],
                    ['stage' => 3, 'name' => 'Toxicore', 'hp' => 90, 'attack' => 110, 'defense' => 105, 'sp_attack' => 60, 'sp_defense' => 70, 'speed' => 50, 'evolve' => null],
                ],
                'learnset' => [
                    ['move' => 'Stone Edge', 'stage' => 1, 'method' => 'level-up', 'level' => 1],
                    ['move' => 'Toxic Spores', 'stage' => 1, 'method' => 'level-up', 'level' => 8],
                    ['move' => 'Earthquake', 'stage' => 3, 'method' => 'level-up', 'level' => 42],
                ],
            ],
        ];
    }
}
