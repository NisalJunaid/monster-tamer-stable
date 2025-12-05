<?php

namespace Database\Seeders;

use App\Models\MonsterSpecies;
use App\Models\MonsterSpeciesStage;
use App\Models\Type;
use Database\Seeders\Data\StarterDex;
use Illuminate\Database\Seeder;

class MonsterSpeciesSeeder extends Seeder
{
    public function run(): void
    {
        $types = Type::all()->keyBy('name');

        foreach (StarterDex::species() as $speciesData) {
            $species = MonsterSpecies::updateOrCreate(
                ['name' => $speciesData['name']],
                [
                    'primary_type_id' => $types[$speciesData['primary_type']]->id,
                    'secondary_type_id' => $speciesData['secondary_type'] ? $types[$speciesData['secondary_type']]->id : null,
                    'capture_rate' => $speciesData['capture_rate'],
                    'rarity_tier' => $speciesData['rarity_tier'],
                    'base_experience' => $speciesData['base_experience'],
                ]
            );

            $stages = [];
            foreach ($speciesData['stages'] as $stageData) {
                $stages[$stageData['stage']] = MonsterSpeciesStage::updateOrCreate(
                    [
                        'species_id' => $species->id,
                        'stage_number' => $stageData['stage'],
                    ],
                    [
                        'name' => $stageData['name'],
                        'hp' => $stageData['hp'],
                        'attack' => $stageData['attack'],
                        'defense' => $stageData['defense'],
                        'sp_attack' => $stageData['sp_attack'],
                        'sp_defense' => $stageData['sp_defense'],
                        'speed' => $stageData['speed'],
                        'evolve_trigger_json' => $stageData['evolve'],
                    ]
                );
            }

            foreach ($speciesData['stages'] as $stageData) {
                $nextStageId = $stages[$stageData['stage'] + 1]->id ?? null;

                if ($nextStageId) {
                    $stages[$stageData['stage']]->update(['evolves_to_stage_id' => $nextStageId]);
                }
            }
        }
    }
}
