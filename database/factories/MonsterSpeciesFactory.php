<?php

namespace Database\Factories;

use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\MonsterSpecies> */
class MonsterSpeciesFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst($this->faker->unique()->word());

        return [
            'name' => $name,
            'primary_type_id' => Type::factory(),
            'secondary_type_id' => null,
            'capture_rate' => $this->faker->numberBetween(45, 120),
            'rarity_tier' => $this->faker->randomElement(['common', 'uncommon', 'rare']),
            'base_experience' => $this->faker->numberBetween(50, 200),
        ];
    }
}
