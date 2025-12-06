<?php

namespace App\Domain\Encounters;

use App\Models\MonsterSpecies;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ZoneSpawnGenerator
{
    public function generate(Zone $zone, array $rules, bool $replaceExisting = true): Collection
    {
        $poolMode = $rules['pool_mode'] ?? 'any';
        $numSpecies = (int) ($rules['num_species'] ?? 8);
        $typeIds = $rules['types'] ?? [];
        $rarityTiers = $rules['rarity_tiers'] ?? [];
        $levelMin = (int) ($rules['level_min'] ?? 1);
        $levelMax = (int) ($rules['level_max'] ?? 8);
        $weightMin = (int) ($rules['weight_min'] ?? 10);
        $weightMax = (int) ($rules['weight_max'] ?? 100);

        $poolQuery = MonsterSpecies::query();

        if ($poolMode === 'type_based' && ! empty($typeIds)) {
            $poolQuery->where(function ($query) use ($typeIds) {
                $query->whereIn('primary_type_id', $typeIds)
                    ->orWhereIn('secondary_type_id', $typeIds);
            });
        } elseif ($poolMode === 'rarity_based' && ! empty($rarityTiers)) {
            $poolQuery->whereIn('rarity_tier', $rarityTiers);
        }

        $candidates = $poolQuery->inRandomOrder()->take($numSpecies)->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $entries = collect();

        foreach ($candidates as $candidate) {
            $entries->push([
                'zone_id' => $zone->id,
                'species_id' => $candidate->id,
                'weight' => random_int($weightMin, $weightMax),
                'min_level' => $levelMin,
                'max_level' => $levelMax,
                'rarity_tier' => $candidate->rarity_tier ?? null,
                'conditions_json' => null,
            ]);
        }

        $normalized = $this->normalizeWeights($entries->pluck('weight')->all());

        $entries = $entries->map(function (array $entry, int $index) use ($normalized) {
            $entry['weight'] = $normalized[$index];

            return $entry;
        });

        return DB::transaction(function () use ($zone, $entries, $replaceExisting) {
            if ($replaceExisting) {
                $zone->spawnEntries()->delete();
            }

            $zone->spawnEntries()->createMany($entries);

            return $zone->spawnEntries()->with('species')->get();
        });
    }

    private function normalizeWeights(array $weights): array
    {
        $total = array_sum($weights);

        if ($total <= 0) {
            return $weights;
        }

        $normalized = [];
        $fractions = [];

        foreach ($weights as $index => $weight) {
            $raw = ($weight / $total) * 1000;
            $base = (int) floor($raw);
            $normalized[$index] = $base;
            $fractions[$index] = $raw - $base;
        }

        $assigned = array_sum($normalized);
        $remaining = 1000 - $assigned;

        arsort($fractions);

        foreach (array_keys($fractions) as $index) {
            if ($remaining <= 0) {
                break;
            }

            $normalized[$index]++;
            $remaining--;
        }

        return $normalized;
    }
}
