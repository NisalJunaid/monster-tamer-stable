<?php

namespace Database\Seeders;

use App\Models\Type;
use App\Models\TypeEffectiveness;
use Illuminate\Database\Seeder;

class TypeEffectivenessSeeder extends Seeder
{
    public function run(): void
    {
        $types = Type::all()->keyBy('name');

        foreach ($types as $attack) {
            foreach ($types as $defend) {
                TypeEffectiveness::updateOrCreate(
                    [
                        'attack_type_id' => $attack->id,
                        'defend_type_id' => $defend->id,
                    ],
                    ['multiplier' => 1.0]
                );
            }
        }

        $double = [
            'Fire' => ['Nature', 'Ice', 'Metal'],
            'Water' => ['Fire', 'Earth', 'Metal'],
            'Nature' => ['Earth', 'Water'],
            'Electric' => ['Water', 'Wind'],
            'Ice' => ['Wind', 'Nature'],
            'Earth' => ['Electric', 'Metal'],
            'Wind' => ['Nature', 'Toxic'],
            'Toxic' => ['Nature', 'Spirit'],
            'Light' => ['Shadow', 'Spirit'],
            'Shadow' => ['Light', 'Spirit'],
            'Spirit' => ['Shadow', 'Light'],
            'Metal' => ['Light', 'Wind'],
        ];

        $half = [
            'Fire' => ['Water', 'Earth'],
            'Water' => ['Nature', 'Toxic'],
            'Nature' => ['Fire', 'Ice'],
            'Electric' => ['Metal'],
            'Ice' => ['Fire', 'Metal'],
            'Earth' => ['Nature', 'Water'],
            'Wind' => ['Metal', 'Ice'],
            'Toxic' => ['Metal', 'Earth'],
            'Light' => ['Metal', 'Fire'],
            'Shadow' => ['Shadow'],
            'Spirit' => ['Light'],
            'Metal' => ['Fire', 'Earth'],
        ];

        $immune = [
            'Electric' => ['Earth'],
        ];

        foreach ($double as $attack => $defenders) {
            foreach ($defenders as $defend) {
                $this->setMultiplier($types[$attack]->id, $types[$defend]->id, 2.0);
            }
        }

        foreach ($half as $attack => $defenders) {
            foreach ($defenders as $defend) {
                $this->setMultiplier($types[$attack]->id, $types[$defend]->id, 0.5);
            }
        }

        foreach ($immune as $attack => $defenders) {
            foreach ($defenders as $defend) {
                $this->setMultiplier($types[$attack]->id, $types[$defend]->id, 0.0);
            }
        }
    }

    private function setMultiplier(int $attackId, int $defendId, float $multiplier): void
    {
        TypeEffectiveness::where('attack_type_id', $attackId)
            ->where('defend_type_id', $defendId)
            ->update(['multiplier' => $multiplier]);
    }
}
