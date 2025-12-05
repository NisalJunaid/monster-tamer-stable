<?php

namespace Database\Factories;

use App\Models\MonsterInstance;
use App\Models\MonsterSpecies;
use App\Models\MonsterSpeciesStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MonsterInstance> */
class MonsterInstanceFactory extends Factory
{
    protected $model = MonsterInstance::class;

    public function definition(): array
    {
        $speciesFactory = MonsterSpecies::factory();

        return [
            'user_id' => User::factory(),
            'species_id' => $speciesFactory,
            'current_stage_id' => MonsterSpeciesStage::factory()->for($speciesFactory, 'species')->state([
                'stage_number' => 1,
            ]),
            'nickname' => null,
            'level' => $this->faker->numberBetween(1, 50),
            'experience' => $this->faker->numberBetween(0, 5000),
            'nature' => $this->faker->randomElement(['Bold', 'Calm', 'Jolly', 'Modest']),
            'iv_json' => [
                'hp' => 31,
                'attack' => 31,
                'defense' => 31,
                'sp_attack' => 31,
                'sp_defense' => 31,
                'speed' => 31,
            ],
            'ev_json' => [
                'hp' => 0,
                'attack' => 0,
                'defense' => 0,
                'sp_attack' => 0,
                'sp_defense' => 0,
                'speed' => 0,
            ],
        ];
    }
}
