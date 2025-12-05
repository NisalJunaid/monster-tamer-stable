<?php

namespace Database\Factories;

use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Move> */
class MoveFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->word()),
            'type_id' => Type::factory(),
            'category' => $this->faker->randomElement(['physical', 'special', 'status']),
            'power' => $this->faker->numberBetween(40, 120),
            'accuracy' => $this->faker->numberBetween(70, 100),
            'pp' => $this->faker->numberBetween(10, 35),
            'priority' => 0,
            'effect_json' => null,
        ];
    }
}
