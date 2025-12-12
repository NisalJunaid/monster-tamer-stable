<?php

namespace App\Domain\Battle;

use App\Models\Battle;
use App\Models\InstanceMove;
use App\Models\MonsterInstance;
use App\Models\TypeEffectiveness;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BattleEngine
{
    private array $typeChart = [];

    public function initialize(User $player1, User $player2, Collection $player1Party, Collection $player2Party, int $seed): array
    {
        return [
            'seed' => $seed,
            'rng_state' => $seed,
            'turn' => 1,
            'next_actor_id' => $player1->id,
            'participants' => [
                $player1->id => $this->buildParticipant($player1, $player1Party),
                $player2->id => $this->buildParticipant($player2, $player2Party),
            ],
            'log' => [],
        ];
    }

    public function applyAction(Battle $battle, int $actorUserId, array $action): array
    {
        $state = $battle->meta_json ?? [];
        $rng = new DeterministicRng($state['seed'], $state['rng_state']);
        $result = [
            'turn' => $state['turn'] ?? 1,
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'events' => [],
        ];

        $actorSide = & $state['participants'][$actorUserId];
        $opponentUserId = $battle->player1_id === $actorUserId ? $battle->player2_id : $battle->player1_id;
        $opponentSide = & $state['participants'][$opponentUserId];

        $activeAttacker = & $actorSide['monsters'][$actorSide['active_index']];
        $activeDefender = & $opponentSide['monsters'][$opponentSide['active_index']];
        $attackerHp = $activeAttacker['current_hp'] ?? 0;

        if ($attackerHp <= 0 && $action['type'] !== 'swap') {
            throw new InvalidArgumentException('Your active monster has fainted. Swap to a healthy monster.');
        }

        if ($attackerHp <= 0 && ! $this->hasHealthySwapTarget($actorSide)) {
            throw new InvalidArgumentException('All of your monsters have fainted.');
        }

        if ($action['type'] === 'swap') {
            // The swap action expects the MonsterInstance::id stored on the battle
            // state as each monster's `id`; sending a different identifier will
            // trigger the "Monster not found on your team." validation below.
            // Swaps update the in-memory state but do not directly write a
            // battle_turns row; the turn is incremented later alongside moves.
            $this->swapActive($actorSide, $action['monster_instance_id']);
            $result['events'][] = [
                'type' => 'swap',
                'active_instance_id' => $action['monster_instance_id'],
            ];

            $activeAttacker = & $actorSide['monsters'][$actorSide['active_index']];
        }

        if ($action['type'] === 'move') {
            if ($this->isAsleep($activeAttacker, $result)) {
                $this->applyResidual($activeAttacker, $result);
                $state['rng_state'] = $rng->currentState();
                $state['turn']++;
                $state['next_actor_id'] = $opponentUserId;

                // A blocked move still advances the in-memory turn counter;
                // database turn_number values are resolved when persisting.

                return [$state, $result, false, null];
            }

            if ($this->isShockedAndImmobilized($activeAttacker, $rng, $result)) {
                $this->applyResidual($activeAttacker, $result);
                $state['rng_state'] = $rng->currentState();
                $state['turn']++;
                $state['next_actor_id'] = $opponentUserId;

                // The state turn counter still advances even when the move
                // does no damage; persistence resolves turn_number separately.

                return [$state, $result, false, null];
            }

            $move = $this->findMoveBySlot($activeAttacker, (int) $action['slot']);
            $damage = $this->calculateDamage($rng, $move, $activeAttacker, $activeDefender, $multipliers);
            $activeDefender['current_hp'] = max(0, $activeDefender['current_hp'] - $damage);
            $result['events'][] = [
                'type' => 'damage',
                'move' => $move['name'],
                'amount' => $damage,
                'target_instance_id' => $activeDefender['id'],
                'multipliers' => $multipliers,
            ];

            if (($move['effect']['status'] ?? null) && $activeDefender['status'] === null && $activeDefender['current_hp'] > 0) {
                $activeDefender['status'] = $this->buildStatus($move['effect']['status']);
                $result['events'][] = [
                    'type' => 'status_applied',
                    'status' => $activeDefender['status'],
                    'target_instance_id' => $activeDefender['id'],
                ];
            }
        }

        $this->applyResidual($activeAttacker, $result);

        $hasEnded = $this->checkBattleEnd($actorSide, $opponentSide, $result, $winnerId);

        $state['rng_state'] = $rng->currentState();
        // Every action (swap or move) increments the shared turn counter here;
        // controllers resolve the persisted battle_turns.turn_number via a
        // helper that inspects the database rather than trusting state.
        $state['turn']++;
        $state['next_actor_id'] = $opponentUserId;
        $state['log'][] = $result;
        $state['participants'][$actorUserId] = $actorSide;
        $state['participants'][$opponentUserId] = $opponentSide;

        return [$state, $result, $hasEnded, $winnerId];
    }

    private function buildParticipant(User $user, Collection $party): array
    {
        $monsters = $party->values()->map(fn (MonsterInstance $instance) => $this->monsterState($instance));

        if ($monsters->isEmpty()) {
            $monsters->push($this->fallbackBrawler());
        }

        return [
            'user_id' => $user->id,
            'active_index' => 0,
            'monsters' => $monsters->all(),
        ];
    }

    private function fallbackBrawler(): array
    {
        return [
            'id' => 0,
            'player_monster_id' => 0,
            'level' => 3,
            'name' => 'Street Brawler',
            'types' => [],
            'type_names' => ['Physical'],
            'stats' => [
                'hp' => 40,
                'attack' => 25,
                'defense' => 20,
                'sp_attack' => 15,
                'sp_defense' => 15,
                'speed' => 20,
            ],
            'max_hp' => 40,
            'current_hp' => 40,
            'status' => null,
            'moves' => $this->fallbackMoves(),
        ];
    }

    private function fallbackMoves(): array
    {
        return [
            [
                'id' => 0,
                'slot' => 1,
                'name' => 'Quick Jab',
                'type_id' => 0,
                'type' => 'Neutral',
                'category' => 'physical',
                'power' => 18,
                'effect' => [],
            ],
            [
                'id' => 0,
                'slot' => 2,
                'name' => 'Spinning Kick',
                'type_id' => 0,
                'type' => 'Neutral',
                'category' => 'physical',
                'power' => 22,
                'effect' => [],
            ],
            [
                'id' => 0,
                'slot' => 3,
                'name' => 'Feint Uppercut',
                'type_id' => 0,
                'type' => 'Neutral',
                'category' => 'physical',
                'power' => 20,
                'effect' => ['status' => 'shock'],
            ],
        ];
    }

    private function monsterState(MonsterInstance $instance): array
    {
        $stage = $instance->currentStage;
        $species = $instance->species;
        $moves = $instance->moves->sortBy('slot')->map(function (InstanceMove $instanceMove) use ($species) {
            $move = $instanceMove->move;

            return [
                'id' => $move->id,
                'slot' => $instanceMove->slot,
                'name' => $move->name,
                'type_id' => $move->type_id,
                'type' => optional($move->type)->name,
                'category' => $move->category,
                'power' => $move->power ?? 0,
                'effect' => $move->effect_json ?? [],
            ];
        })->values()->all();

        return [
            'id' => $instance->id,
            'player_monster_id' => $instance->id,
            'level' => $instance->level,
            'name' => $instance->nickname ?: $stage->name,
            'types' => array_values(array_filter([$species->primary_type_id, $species->secondary_type_id])),
            'type_names' => array_values(array_filter([$species->primaryType?->name, $species->secondaryType?->name])),
            'stats' => [
                'hp' => $stage->hp,
                'attack' => $stage->attack,
                'defense' => $stage->defense,
                'sp_attack' => $stage->sp_attack,
                'sp_defense' => $stage->sp_defense,
                'speed' => $stage->speed,
            ],
            'max_hp' => $stage->hp,
            'current_hp' => $stage->hp,
            'status' => null,
            'moves' => $moves,
        ];
    }

    private function mergeMonsterState(array $monster, ?array $base): array
    {
        if ($base === null) {
            $monster['moves'] = $this->normalizeMoves($monster['moves'] ?? []);

            return $monster;
        }

        $merged = [
            ...$base,
            ...$monster,
        ];

        $merged['name'] = $monster['name'] ?? $base['name'] ?? 'Unknown';
        $merged['level'] = $monster['level'] ?? $base['level'] ?? null;
        $merged['types'] = $monster['types'] ?? $base['types'] ?? [];
        $merged['type_names'] = $monster['type_names'] ?? $base['type_names'] ?? [];
        $merged['stats'] = $monster['stats'] ?? $base['stats'] ?? [];
        $merged['max_hp'] = $monster['max_hp'] ?? $base['max_hp'] ?? null;

        $merged['moves'] = $this->mergeMoves(
            $monster['moves'] ?? [],
            $base['moves'] ?? [],
        );

        return $merged;
    }

    private function mergeMoves(array $existingMoves, array $baseMoves): array
    {
        $baseBySlot = collect($baseMoves)->keyBy(fn (array $move) => $move['slot'] ?? null);
        $merged = [];

        foreach ($existingMoves as $index => $move) {
            $slot = $move['slot'] ?? ($move['id'] ?? ($index + 1));
            $base = $slot !== null ? $baseBySlot->get($slot) : null;
            $merged[] = $base ? array_merge($base, $move) : $this->normalizeMove($move, (int) $slot);
        }

        $mergedSlots = collect($merged)->pluck('slot')->filter()->all();

        foreach ($baseBySlot as $slot => $move) {
            if (! in_array($slot, $mergedSlots, true)) {
                $merged[] = $move;
            }
        }

        return $this->normalizeMoves($merged);
    }

    private function normalizeMoves(array $moves): array
    {
        return collect($moves)
            ->values()
            ->map(function (array $move, int $index) {
                $slot = $move['slot'] ?? ($move['id'] ?? ($index + 1));

                return $this->normalizeMove($move, (int) $slot);
            })
            ->all();
    }

    private function normalizeMove(array $move, int $slot): array
    {
        return [
            'id' => $move['id'] ?? $slot,
            'slot' => $slot,
            'name' => $move['name'] ?? 'Move',
            'type_id' => $move['type_id'] ?? $move['typeId'] ?? ($move['type']['id'] ?? null) ?? null,
            'type' => $move['type'] ?? ($move['type_name'] ?? $move['typeName'] ?? 'Neutral'),
            'category' => $move['category'] ?? 'physical',
            'power' => $move['power'] ?? null,
            'effect' => $move['effect'] ?? $move['effect_json'] ?? [],
            ...Arr::except($move, ['slot', 'name', 'type', 'category', 'power', 'effect']),
        ];
    }

    public function hydrateUiState(array $state): array
    {
        $participants = $state['participants'] ?? [];

        if ($participants === []) {
            return $state;
        }

        $monsterIds = collect($participants)
            ->flatMap(fn (array $participant) => collect($participant['monsters'] ?? [])->pluck('id'))
            ->filter()
            ->unique()
            ->values();

        $instances = MonsterInstance::query()
            ->with(['currentStage', 'species.primaryType', 'species.secondaryType', 'moves.move.type'])
            ->whereIn('id', $monsterIds)
            ->get()
            ->keyBy('id');

        $state['participants'] = collect($participants)
            ->map(function (array $participant) use ($instances) {
                $monsters = collect($participant['monsters'] ?? [])
                    ->map(function (array $monster) use ($instances) {
                        $monsterId = $monster['id'] ?? null;
                        $base = $monsterId !== null ? $instances->get($monsterId) : null;
                        $baseState = $base ? $this->monsterState($base) : null;

                        return $this->mergeMonsterState($monster, $baseState);
                    })
                    ->values()
                    ->all();

                $participant['monsters'] = $monsters;

                return $participant;
            })
            ->all();

        return $state;
    }

    private function isAsleep(array &$monster, array &$result): bool
    {
        if (($monster['status']['name'] ?? null) !== 'sleep') {
            return false;
        }

        $monster['status']['turns']--;
        $result['events'][] = [
            'type' => 'status_block',
            'status' => 'sleep',
            'remaining_turns' => max(0, $monster['status']['turns']),
        ];

        if ($monster['status']['turns'] <= 0) {
            $monster['status'] = null;
        }

        return true;
    }

    private function isShockedAndImmobilized(array &$monster, DeterministicRng $rng, array &$result): bool
    {
        if (($monster['status']['name'] ?? null) !== 'shock') {
            return false;
        }

        if ($rng->nextFloat() < 0.25) {
            $result['events'][] = [
                'type' => 'status_block',
                'status' => 'shock',
            ];

            return true;
        }

        return false;
    }

    private function hasHealthySwapTarget(array $participant): bool
    {
        $activeIndex = $participant['active_index'] ?? 0;

        foreach ($participant['monsters'] ?? [] as $index => $monster) {
            if ($index === $activeIndex) {
                continue;
            }

            if (($monster['current_hp'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    private function swapActive(array &$participant, int $targetInstanceId): void
    {
        // PvP swap implementation: expects the MonsterInstance::id stored as each participant
        // monster's `id` in battle meta and fails with "Monster not found on your team." if
        // no matching entry is found for the viewer.
        foreach ($participant['monsters'] as $index => $monster) {
            if ($monster['id'] === $targetInstanceId) {
                if ($monster['current_hp'] <= 0) {
                    throw new InvalidArgumentException('Cannot switch to a fainted monster.');
                }

                $participant['active_index'] = $index;

                return;
            }
        }

        throw new InvalidArgumentException('Monster not found on your team.');
    }

    private function findMoveBySlot(array $monster, int $slot): array
    {
        foreach ($monster['moves'] as $move) {
            if ($move['slot'] === $slot) {
                return $move;
            }
        }

        throw new InvalidArgumentException('Move slot not found');
    }

    private function calculateDamage(DeterministicRng $rng, array $move, array $attacker, array $defender, ?array &$multipliers = []): int
    {
        $attackStat = $move['category'] === 'special' ? $attacker['stats']['sp_attack'] : $attacker['stats']['attack'];
        $defenseStat = $move['category'] === 'special' ? $defender['stats']['sp_defense'] : $defender['stats']['defense'];

        $base = (((2 * $attacker['level']) / 5 + 2) * $move['power'] * ($attackStat / max(1, $defenseStat))) / 50 + 2;
        $stab = in_array($move['type_id'], $attacker['types'], true) ? 1.5 : 1.0;
        $typeMultiplier = $this->typeMultiplier($move['type_id'], $defender['types']);
        $crit = $rng->nextFloat() < 0.1 ? 1.5 : 1.0;
        $randomFactor = 0.85 + ($rng->nextFloat() * 0.15);

        $multipliers = [
            'stab' => $stab,
            'type' => $typeMultiplier,
            'crit' => $crit,
            'random' => $randomFactor,
        ];

        $damage = (int) floor($base * $stab * $typeMultiplier * $crit * $randomFactor);

        if ($typeMultiplier === 1.0 && $crit === 1.0 && $damage > ($defender['max_hp'] * 0.4)) {
            $damage = (int) floor($damage * 0.85);
        }

        return max(1, $damage);
    }

    private function typeMultiplier(int $attackTypeId, array $defenderTypes): float
    {
        $this->ensureTypeChartLoaded();

        return array_reduce($defenderTypes, function (float $carry, int $defendTypeId) use ($attackTypeId) {
            $multiplier = Arr::get($this->typeChart, $attackTypeId.'.'.$defendTypeId, 1.0);

            return $carry * $multiplier;
        }, 1.0);
    }

    private function ensureTypeChartLoaded(): void
    {
        if ($this->typeChart !== []) {
            return;
        }

        $this->typeChart = TypeEffectiveness::query()
            ->get()
            ->groupBy('attack_type_id')
            ->map(fn (Collection $group) => $group->keyBy('defend_type_id')->map->multiplier)
            ->toArray();
    }

    private function applyResidual(array &$monster, array &$result): void
    {
        if (($monster['status']['name'] ?? null) === null) {
            return;
        }

        if ($monster['status']['name'] === 'burn') {
            $damage = max(1, (int) floor($monster['max_hp'] * 0.1));
            $monster['current_hp'] = max(0, $monster['current_hp'] - $damage);
            $result['events'][] = [
                'type' => 'residual',
                'status' => 'burn',
                'amount' => $damage,
                'target_instance_id' => $monster['id'],
            ];
        }

        if ($monster['status']['name'] === 'poison') {
            $damage = max(1, (int) floor($monster['max_hp'] * 0.125));
            $monster['current_hp'] = max(0, $monster['current_hp'] - $damage);
            $result['events'][] = [
                'type' => 'residual',
                'status' => 'poison',
                'amount' => $damage,
                'target_instance_id' => $monster['id'],
            ];
        }
    }

    private function buildStatus(string $name): array
    {
        return match ($name) {
            'sleep' => ['name' => 'sleep', 'turns' => 2],
            'shock' => ['name' => 'shock'],
            'burn' => ['name' => 'burn'],
            'poison' => ['name' => 'poison'],
            default => ['name' => $name],
        };
    }

    private function checkBattleEnd(array $actorSide, array $opponentSide, array &$result, ?int &$winnerId): bool
    {
        $winnerId = null;
        $actorAlive = $this->hasAvailableMonster($actorSide);
        $opponentAlive = $this->hasAvailableMonster($opponentSide);

        if (! $actorAlive && ! $opponentAlive) {
            return false;
        }

        if (! $opponentAlive) {
            $winnerId = $actorSide['user_id'];
            $result['events'][] = [
                'type' => 'battle_end',
                'winner_user_id' => $winnerId,
            ];

            return true;
        }

        if (! $actorAlive) {
            $winnerId = $opponentSide['user_id'];
            $result['events'][] = [
                'type' => 'battle_end',
                'winner_user_id' => $winnerId,
            ];

            return true;
        }

        return false;
    }

    private function hasAvailableMonster(array &$side): bool
    {
        if (($side['monsters'][$side['active_index']]['current_hp'] ?? 0) > 0) {
            return true;
        }

        foreach ($side['monsters'] as $index => $monster) {
            if ($monster['current_hp'] > 0) {
                $side['active_index'] = $index;

                return true;
            }
        }

        return false;
    }
}
