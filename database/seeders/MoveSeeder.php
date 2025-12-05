<?php

namespace Database\Seeders;

use App\Models\Move;
use App\Models\Type;
use Illuminate\Database\Seeder;

class MoveSeeder extends Seeder
{
    public function run(): void
    {
        $typeLookup = Type::all()->keyBy('name');

        $moves = [
            ['name' => 'Ember', 'type' => 'Fire', 'category' => 'special', 'power' => 40, 'accuracy' => 100, 'pp' => 25, 'priority' => 0, 'effect' => null],
            ['name' => 'Flame Burst', 'type' => 'Fire', 'category' => 'special', 'power' => 70, 'accuracy' => 100, 'pp' => 15, 'priority' => 0, 'effect' => null],
            ['name' => 'Inferno', 'type' => 'Fire', 'category' => 'special', 'power' => 100, 'accuracy' => 75, 'pp' => 5, 'priority' => 0, 'effect' => ['status' => 'burn']],
            ['name' => 'Water Jet', 'type' => 'Water', 'category' => 'physical', 'power' => 40, 'accuracy' => 100, 'pp' => 35, 'priority' => 1, 'effect' => null],
            ['name' => 'Bubble Beam', 'type' => 'Water', 'category' => 'special', 'power' => 65, 'accuracy' => 100, 'pp' => 20, 'priority' => 0, 'effect' => ['speed' => -1]],
            ['name' => 'Tsunami Crash', 'type' => 'Water', 'category' => 'physical', 'power' => 95, 'accuracy' => 90, 'pp' => 10, 'priority' => 0, 'effect' => null],
            ['name' => 'Leaf Blade', 'type' => 'Nature', 'category' => 'physical', 'power' => 90, 'accuracy' => 100, 'pp' => 15, 'priority' => 0, 'effect' => ['crit_rate' => 1]],
            ['name' => 'Gust', 'type' => 'Wind', 'category' => 'special', 'power' => 40, 'accuracy' => 100, 'pp' => 35, 'priority' => 0, 'effect' => null],
            ['name' => 'Hurricane Bloom', 'type' => 'Nature', 'category' => 'special', 'power' => 110, 'accuracy' => 70, 'pp' => 5, 'priority' => 0, 'effect' => ['chance_confuse' => 0.3]],
            ['name' => 'Thunder Jolt', 'type' => 'Electric', 'category' => 'special', 'power' => 40, 'accuracy' => 100, 'pp' => 30, 'priority' => 0, 'effect' => ['chance_paralyze' => 0.1]],
            ['name' => 'Metal Claw', 'type' => 'Metal', 'category' => 'physical', 'power' => 50, 'accuracy' => 95, 'pp' => 35, 'priority' => 0, 'effect' => ['chance_attack_boost' => 0.1]],
            ['name' => 'Gigawatt Surge', 'type' => 'Electric', 'category' => 'special', 'power' => 110, 'accuracy' => 70, 'pp' => 5, 'priority' => 0, 'effect' => null],
            ['name' => 'Stone Edge', 'type' => 'Earth', 'category' => 'physical', 'power' => 100, 'accuracy' => 80, 'pp' => 5, 'priority' => 0, 'effect' => ['crit_rate' => 1]],
            ['name' => 'Toxic Spores', 'type' => 'Toxic', 'category' => 'status', 'power' => null, 'accuracy' => 90, 'pp' => 15, 'priority' => 0, 'effect' => ['status' => 'poison']],
            ['name' => 'Earthquake', 'type' => 'Earth', 'category' => 'physical', 'power' => 100, 'accuracy' => 100, 'pp' => 10, 'priority' => 0, 'effect' => ['targets' => 'all_adjacent']],
        ];

        foreach ($moves as $move) {
            Move::updateOrCreate(
                ['name' => $move['name']],
                [
                    'type_id' => $typeLookup[$move['type']]->id,
                    'category' => $move['category'],
                    'power' => $move['power'],
                    'accuracy' => $move['accuracy'],
                    'pp' => $move['pp'],
                    'priority' => $move['priority'],
                    'effect_json' => $move['effect'],
                ]
            );
        }
    }
}
