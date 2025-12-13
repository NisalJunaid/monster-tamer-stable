<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Move;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        Item::updateOrCreate(
            ['key' => 'potion_small'],
            [
                'name' => 'Potion',
                'description' => 'Heals 30 HP.',
                'category' => 'medicine',
                'is_consumable' => true,
                'stack_limit' => 999,
                'effect_type' => 'heal_hp',
                'effect_payload' => ['amount' => 30],
            ]
        );

        // Pick an existing move (replace this selection if you have move keys)
        $move = Move::query()->first();
        if ($move) {
            Item::updateOrCreate(
                ['key' => 'tm_example'],
                [
                    'name' => 'TM (Example)',
                    'description' => 'Teaches a move.',
                    'category' => 'tm',
                    'is_consumable' => true,
                    'stack_limit' => 99,
                    'effect_type' => 'teach_move',
                    'effect_payload' => ['move_id' => $move->id],
                ]
            );
        }
    }
}
