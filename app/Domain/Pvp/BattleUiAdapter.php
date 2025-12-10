<?php

namespace App\Domain\Pvp;

use App\Models\Battle;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class BattleUiAdapter
{
    public function toWildUiState(Battle $battle, User $viewer): array
    {
        $state = $battle->meta_json ?? [];
        $participants = $state['participants'] ?? [];

        $viewerSide = $participants[$viewer->id] ?? ['monsters' => [], 'active_index' => 0];
        $opponentId = $battle->player1_id === $viewer->id ? $battle->player2_id : $battle->player1_id;
        $opponentSide = $participants[$opponentId] ?? ['monsters' => [], 'active_index' => 0];

        $playerMonsters = $this->normalizeMonsters($viewerSide['monsters'] ?? []);
        $opponentMonsters = $this->normalizeMonsters($opponentSide['monsters'] ?? []);

        $playerActiveId = $this->resolveActiveId($viewerSide, $playerMonsters);
        $opponentActive = $this->resolveActiveMonster($opponentSide, $opponentMonsters);

        return [
            'active' => $battle->status === 'active',
            'resolved' => $battle->status !== 'active',
            'turn' => $state['turn'] ?? 1,
            'next_actor_id' => $state['next_actor_id'] ?? null,
            'player_active_monster_id' => $playerActiveId,
            'player_monsters' => $playerMonsters,
            'opponent_monsters' => $opponentMonsters,
            'wild' => $opponentActive,
            'last_action_log' => $this->transformLog($state['log'] ?? [], $viewer, $battle),
            'wild_ai' => false,
        ];
    }

    private function normalizeMonsters(array $monsters): array
    {
        return collect($monsters)
            ->map(function (array $monster, int $index) {
                $moves = collect($monster['moves'] ?? [])->map(function (array $move, int $moveIndex) {
                    $slot = $move['slot'] ?? ($moveIndex + 1);

                    return [
                        'id' => $move['id'] ?? $slot,
                        'slot' => $slot,
                        'name' => $move['name'] ?? 'Move',
                        'type' => $move['type'] ?? 'Neutral',
                        'category' => $move['category'] ?? 'physical',
                        'power' => $move['power'] ?? null,
                        'effect' => $move['effect'] ?? [],
                        'style' => (string) $slot,
                    ];
                })->values()->all();

                return [
                    'id' => $monster['id'] ?? $index,
                    'name' => $monster['name'] ?? 'Unknown',
                    'level' => $monster['level'] ?? null,
                    'types' => $monster['types'] ?? [],
                    'type_names' => $monster['type_names'] ?? [],
                    'stats' => $monster['stats'] ?? [],
                    'max_hp' => $monster['max_hp'] ?? null,
                    'current_hp' => $monster['current_hp'] ?? null,
                    'status' => $monster['status'] ?? null,
                    'moves' => $moves,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveActiveId(array $side, array $monsters): ?int
    {
        $activeIndex = $side['active_index'] ?? 0;
        $fallback = $monsters[0]['id'] ?? null;

        return $monsters[$activeIndex]['id'] ?? $fallback;
    }

    private function resolveActiveMonster(array $side, array $monsters): ?array
    {
        $activeIndex = $side['active_index'] ?? 0;

        return $monsters[$activeIndex] ?? $monsters[0] ?? null;
    }

    private function transformLog(array $log, User $viewer, Battle $battle): array
    {
        return collect($log)
            ->map(function (array $entry) use ($viewer, $battle) {
                $actorId = $entry['actor_user_id'] ?? null;
                $action = $entry['action'] ?? [];
                $events = new Collection($entry['events'] ?? []);
                $damageEvent = $events->firstWhere('type', 'damage') ?? $events->first();
                $actorLabel = match ($actorId) {
                    $viewer->id => 'you',
                    $battle->player1_id => $battle->player1?->name ?? 'opponent',
                    $battle->player2_id => $battle->player2?->name ?? 'opponent',
                    default => 'unknown',
                };

                return [
                    'actor' => $actorLabel,
                    'type' => $action['type'] ?? $damageEvent['type'] ?? 'action',
                    'style' => $action['slot'] ?? $action['style'] ?? null,
                    'damage' => $damageEvent['amount'] ?? null,
                    'multiplier' => Arr::get($damageEvent, 'multipliers.type') ?? $damageEvent['multipliers'] ?? null,
                ];
            })
            ->values()
            ->all();
    }
}
