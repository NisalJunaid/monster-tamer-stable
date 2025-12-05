<?php

namespace Database\Seeders;

use App\Models\MonsterSpecies;
use App\Models\Move;
use App\Models\SpeciesLearnset;
use Database\Seeders\Data\StarterDex;
use Illuminate\Database\Seeder;

class SpeciesLearnsetSeeder extends Seeder
{
    public function run(): void
    {
        $speciesLookup = MonsterSpecies::all()->keyBy('name');
        $moveLookup = Move::all()->keyBy('name');

        foreach (StarterDex::species() as $speciesData) {
            $species = $speciesLookup[$speciesData['name']];

            foreach ($speciesData['learnset'] as $entry) {
                SpeciesLearnset::updateOrCreate(
                    [
                        'species_id' => $species->id,
                        'stage_number' => $entry['stage'],
                        'move_id' => $moveLookup[$entry['move']]->id,
                        'learn_method' => $entry['method'],
                    ],
                    [
                        'learn_level' => $entry['level'],
                    ]
                );
            }
        }
    }
}
