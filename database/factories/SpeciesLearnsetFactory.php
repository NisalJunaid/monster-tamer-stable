<?php

namespace Database\Factories;

use App\Models\MonsterSpecies;
use App\Models\Move;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\SpeciesLearnset> */
class SpeciesLearnsetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'species_id' => MonsterSpecies::factory(),
            'stage_number' => 1,
            'move_id' => Move::factory(),
            'learn_level' => $this->faker->numberBetween(1, 50),
            'learn_method' => 'level-up',
        ];
    }
}
