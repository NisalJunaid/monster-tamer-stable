<?php

namespace Database\Factories;

use App\Models\MonsterSpecies;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\MonsterSpeciesStage> */
class MonsterSpeciesStageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'species_id' => MonsterSpecies::factory(),
            'stage_number' => 1,
            'name' => $this->faker->unique()->word(),
            'hp' => $this->faker->numberBetween(40, 80),
            'attack' => $this->faker->numberBetween(40, 80),
            'defense' => $this->faker->numberBetween(40, 80),
            'sp_attack' => $this->faker->numberBetween(40, 80),
            'sp_defense' => $this->faker->numberBetween(40, 80),
            'speed' => $this->faker->numberBetween(40, 80),
            'evolves_to_stage_id' => null,
            'evolve_trigger_json' => null,
        ];
    }
}
