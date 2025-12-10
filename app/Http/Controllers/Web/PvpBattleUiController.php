<?php

namespace App\Http\Controllers\Web;

use App\Domain\Battle\MonsterSwitchService;
use App\Domain\Battle\BattleEngine;
use App\Domain\Battle\TurnNumberService;
use App\Domain\Pvp\BattleUiAdapter;
use App\Domain\Pvp\PvpRankingService;
use App\Events\BattleUpdated;
use App\Http\Controllers\Controller;
use App\Models\Battle;
use App\Models\BattleTurn;
use App\Models\PlayerMonster;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class PvpBattleUiController extends Controller
{
    public function __construct(
        private readonly BattleEngine $engine,
        private readonly PvpRankingService $rankingService,
        private readonly BattleUiAdapter $adapter,
        private readonly MonsterSwitchService $monsterSwitchService,
        private readonly TurnNumberService $turnNumberService,
    ) {
    }

    public function showWildUi(Request $request, Battle $battle)
    {
        $viewer = $this->assertParticipant($request, $battle);
        $battle->loadMissing(['player1', 'player2', 'winner']);

        return view('pvp.wild_battle', [
            'battle' => $battle,
            'viewer' => $viewer,
            'initialPayload' => $this->buildPayload($battle, $viewer),
        ]);
    }

    public function move(Request $request, Battle $battle): JsonResponse
    {
        $viewer = $this->assertParticipant($request, $battle);
        $payload = $request->validate([
            'type' => ['nullable', 'in:move'],
            'style' => ['nullable'],
            'slot' => ['nullable', 'integer', 'min:1', 'max:4'],
        ]);

        $slot = (int) ($payload['slot'] ?? $payload['style'] ?? 0);

        if ($slot < 1 || $slot > 4) {
            return response()->json(['message' => 'Invalid move selection.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->applyAndRespond($battle, $viewer, [
            'type' => 'move',
            'slot' => $slot,
        ]);
    }

    public function switchActive(Request $request, Battle $battle): JsonResponse
    {
        $viewer = $this->assertParticipant($request, $battle);
        $payload = $request->validate([
            'type' => ['nullable', 'in:swap'],
            'player_monster_id' => ['required', 'integer'],
        ]);

        return $this->applyAndRespond($battle, $viewer, [
            'type' => 'swap',
            'player_monster_id' => (int) $payload['player_monster_id'],
        ]);
    }

    public function run(Request $request, Battle $battle): JsonResponse
    {
        $viewer = $this->assertParticipant($request, $battle);

        if ($battle->status !== 'active') {
            return response()->json($this->buildPayload($battle, $viewer));
        }

        $state = $battle->meta_json ?? [];
        $state['log'][] = [
            'turn' => $state['turn'] ?? 1,
            'actor_user_id' => $viewer->id,
            'action' => ['type' => 'run'],
            'events' => [['type' => 'forfeit']],
        ];

        $opponentId = $this->opponentId($battle, $viewer);

        $battle->update([
            'meta_json' => $state,
            'status' => 'completed',
            'winner_user_id' => $opponentId,
            'ended_at' => now(),
        ]);

        $this->rankingService->handleBattleCompletion($battle->fresh());

        broadcast(new BattleUpdated(
            battleId: $battle->id,
            state: $state,
            status: 'completed',
            nextActorId: null,
            winnerUserId: $opponentId,
        ));

        return response()->json($this->buildPayload($battle->fresh(), $viewer));
    }

    private function applyAndRespond(Battle $battle, User $actor, array $action): JsonResponse
    {
        if ($battle->status !== 'active') {
            return response()->json(['message' => 'Battle is not active.'], Response::HTTP_CONFLICT);
        }

        $meta = $battle->meta_json ?? [];

        if (($meta['next_actor_id'] ?? null) !== $actor->id) {
            return response()->json(['message' => 'It is not your turn yet.'], Response::HTTP_CONFLICT);
        }

        try {
            if ($action['type'] === 'swap') {
                [$state, $result, $hasEnded, $winnerId] = $this->applySwap($battle, $actor, $action);
            } else {
                [$state, $result, $hasEnded, $winnerId] = $this->engine->applyAction($battle, $actor->id, $action);
            }
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $turnNumber = $this->turnNumberService->nextTurnNumber($battle);
        $this->synchronizeLoggedTurn($state, $result, $turnNumber);

        $battle->update([
            'meta_json' => $state,
            'status' => $hasEnded ? 'completed' : 'active',
            'winner_user_id' => $winnerId,
            'ended_at' => $hasEnded ? now() : null,
        ]);

        BattleTurn::query()->create([
            'battle_id' => $battle->id,
            'turn_number' => $turnNumber,
            'actor_user_id' => $actor->id,
            'action_json' => $action,
            'result_json' => $result,
        ]);

        if ($hasEnded && $winnerId !== null) {
            $this->rankingService->handleBattleCompletion($battle->fresh());
        }

        broadcast(new BattleUpdated(
            battleId: $battle->id,
            state: $state,
            status: $hasEnded ? 'completed' : 'active',
            nextActorId: $state['next_actor_id'] ?? null,
            winnerUserId: $winnerId,
        ));

        return response()->json($this->buildPayload($battle->fresh(), $actor));
    }

    private function synchronizeLoggedTurn(array &$state, array &$result, int $turnNumber): void
    {
        $result['turn'] = $turnNumber;

        if (! empty($state['log'])) {
            $lastIndex = array_key_last($state['log']);

            if ($lastIndex !== null) {
                $state['log'][$lastIndex]['turn'] = $turnNumber;
            }
        }
    }

    private function applySwap(Battle $battle, User $actor, array $action): array
    {
        $state = $battle->meta_json ?? [];
        $viewerSide = $state['participants'][$actor->id] ?? null;

        if (! $viewerSide) {
            throw new InvalidArgumentException('Monster not found on your team.');
        }

        $ownedMonsters = PlayerMonster::query()
            ->where('user_id', $actor->id)
            ->orderByDesc('is_in_team')
            ->orderBy('team_slot')
            ->orderByDesc('level')
            ->take(count($viewerSide['monsters'] ?? []))
            ->get()
            ->values();

        $playerMonsters = collect($viewerSide['monsters'] ?? [])
            ->map(function (array $monster, int $index) use ($ownedMonsters) {
                $playerMonsterId = $monster['player_monster_id']
                    ?? $monster['id']
                    ?? $monster['monster_instance_id']
                    ?? $monster['instance_id']
                    ?? null;

                if ($playerMonsterId === null && $ownedMonsters->has($index)) {
                    $playerMonsterId = $ownedMonsters[$index]->id;
                }

                $playerMonsterId = is_numeric($playerMonsterId) ? (int) $playerMonsterId : null;

                return [
                    ...$monster,
                    'id' => $playerMonsterId,
                    'player_monster_id' => $playerMonsterId,
                ];
            })
            ->values()
            ->all();

        $activeIndex = $viewerSide['active_index'] ?? 0;
        $activePlayerMonsterId = $playerMonsters[$activeIndex]['player_monster_id'] ?? null;

        $wildLikeState = [
            'player_monsters' => $playerMonsters,
            'player_active_monster_id' => $activePlayerMonsterId,
            'turn' => $state['turn'] ?? 1,
            'last_action_log' => [],
        ];

        \Log::info('PVP state before switch', [
            'user_id' => $actor->id,
            'player_monsters' => $wildLikeState['player_monsters'],
        ]);

        $log = [];
        $result = $this->monsterSwitchService->switchPlayerMonster(
            $wildLikeState,
            (int) $action['player_monster_id'],
            $log,
            $actor->id,
        );

        $targetIndex = $result['index'] ?? null;
        $newActiveId = $result['id'] ?? ($wildLikeState['player_active_monster_id'] ?? null);

        if ($targetIndex === null) {
            throw new InvalidArgumentException('Monster not found on your team.');
        }

        $viewerSide['monsters'] = $wildLikeState['player_monsters'];
        $viewerSide['active_index'] = $targetIndex;

        $state['participants'][$actor->id] = $viewerSide;
        // MonsterSwitchService increments the wild-like state's turn counter;
        // that value is copied back here so the switch shares the same turn
        // numbering the database insert will later use.
        $state['turn'] = $wildLikeState['turn'] ?? ($state['turn'] ?? 1);
        $state['next_actor_id'] = $this->opponentId($battle, $actor);
        $state['log'][] = [
            'turn' => $state['turn'] ?? 1,
            'actor_user_id' => $actor->id,
            'action' => [
                'type' => 'swap',
                'player_monster_id' => $newActiveId,
            ],
            'events' => [
                [
                    'type' => 'swap',
                    'active_instance_id' => $viewerSide['monsters'][$targetIndex]['id'] ?? $newActiveId,
                ],
            ],
        ];

        return [$state, ['turn' => $state['turn'], 'events' => [['type' => 'swap']]], false, null];
    }

    private function buildPayload(Battle $battle, User $viewer): array
    {
        $battle->loadMissing(['player1', 'player2']);
        $state = $battle->meta_json ?? [];
        $participants = $state['participants'] ?? [];

        $opponentUser = $battle->player1_id === $viewer->id ? $battle->player2 : $battle->player1;
        $viewerSide = $participants[$viewer->id] ?? ['monsters' => [], 'active_index' => 0];
        $opponentSide = $opponentUser ? ($participants[$opponentUser->id] ?? ['monsters' => [], 'active_index' => 0]) : ['monsters' => [], 'active_index' => 0];

        $viewerSide['monsters'] = $this->hydrateParticipantMonsters($viewerSide['monsters'] ?? [], $viewer);

        if ($opponentUser) {
            $opponentSide['monsters'] = $this->hydrateParticipantMonsters($opponentSide['monsters'] ?? [], $opponentUser);
            $state['participants'][$opponentUser->id] = $opponentSide;
        }

        $state['participants'][$viewer->id] = $viewerSide;
        $battle->setAttribute('meta_json', $state);

        $wildState = $this->adapter->toWildUiState($battle, $viewer);
        $opponent = $wildState['wild'] ?? [];

        \Log::info('PVP UI state for viewer', [
            'user_id' => $viewer->id,
            'player_monster_ids' => array_column($wildState['player_monsters'] ?? [], 'player_monster_id'),
            'opponent_monster_ids' => array_column($wildState['opponent_monsters'] ?? [], 'player_monster_id'),
        ]);

        return [
            'ticket' => [
                'id' => $battle->id,
                'status' => $battle->status,
                'species' => [
                    'name' => $opponent['name'] ?? 'Opponent',
                    'level' => $opponent['level'] ?? null,
                ],
            ],
            'battle' => $wildState,
            'user_id' => $viewer->id,
        ];
    }

    private function assertParticipant(Request $request, Battle $battle): User
    {
        $user = $request->user();

        if (! in_array($user->id, [$battle->player1_id, $battle->player2_id], true)) {
            abort(Response::HTTP_FORBIDDEN, 'You are not part of this battle.');
        }

        return $user;
    }

    private function opponentId(Battle $battle, User $viewer): int
    {
        return $battle->player1_id === $viewer->id ? $battle->player2_id : $battle->player1_id;
    }

    private function hydrateParticipantMonsters(array $monsters, User $owner): array
    {
        $ownedMonsters = PlayerMonster::query()
            ->where('user_id', $owner->id)
            ->orderByDesc('is_in_team')
            ->orderBy('team_slot')
            ->orderByDesc('level')
            ->take(count($monsters))
            ->get()
            ->values();

        return collect($monsters)
            ->map(function (array $monster, int $index) use ($ownedMonsters) {
                $playerMonsterId = $monster['player_monster_id']
                    ?? $monster['id']
                    ?? $monster['monster_instance_id']
                    ?? $monster['instance_id']
                    ?? null;

                if ($playerMonsterId === null && $ownedMonsters->has($index)) {
                    $playerMonsterId = $ownedMonsters[$index]->id;
                }

                $playerMonsterId = is_numeric($playerMonsterId) ? (int) $playerMonsterId : null;

                return [
                    ...$monster,
                    'id' => $monster['id'] ?? $playerMonsterId ?? $monster['instance_id'] ?? $monster['monster_instance_id'] ?? null,
                    'instance_id' => $monster['instance_id'] ?? $monster['monster_instance_id'] ?? $monster['id'] ?? $playerMonsterId,
                    'player_monster_id' => $playerMonsterId,
                ];
            })
            ->values()
            ->all();
    }
}
