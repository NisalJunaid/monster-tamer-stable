<?php

namespace Database\Factories;

use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\TypeEffectiveness> */
class TypeEffectivenessFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attack_type_id' => Type::factory(),
            'defend_type_id' => Type::factory(),
            'multiplier' => $this->faker->randomFloat(2, 0.5, 2.0),
        ];
    }
}
