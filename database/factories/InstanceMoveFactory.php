<?php

namespace Database\Factories;

use App\Models\MonsterInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\InstanceMove> */
class InstanceMoveFactory extends Factory
{
    public function definition(): array
    {
        return [
            'monster_instance_id' => MonsterInstance::factory(),
            'move_id' => \App\Models\Move::factory(),
            'slot' => $this->faker->numberBetween(1, 4),
        ];
    }
}
