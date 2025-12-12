<?php

namespace App\Jobs;

use App\Domain\Battle\TurnNumberService;
use App\Domain\Pvp\TurnTimerService;
use App\Events\BattleUpdated;
use App\Models\Battle;
use App\Models\BattleTurn;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PvpTurnTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $battleId,
        public int $expectedActorUserId,
        public string $expectedTurnEndsAt,
        public int $expectedTurnNumber,
    ) {
    }

    public function handle(TurnNumberService $turnNumberService, TurnTimerService $turnTimerService): void
    {
        $shouldBroadcast = false;
        $stateForBroadcast = null;
        $nextActorId = null;
        $winnerId = null;
        $battle = null;

        DB::transaction(function () use (&$shouldBroadcast, &$stateForBroadcast, &$nextActorId, &$winnerId, &$battle, $turnNumberService, $turnTimerService) {
            $battle = Battle::query()->lockForUpdate()->find($this->battleId);

            if (! $battle || $battle->status !== 'active') {
                return;
            }

            $state = $battle->meta_json ?? [];
            $currentNextActorId = $state['next_actor_id'] ?? null;
            $currentTurnEndsAt = $state['turn_ends_at'] ?? null;
            $currentTurnNumber = $state['turn'] ?? null;

            if ($currentNextActorId === null || $currentTurnEndsAt === null || $currentTurnNumber === null) {
                return;
            }

            if (
                (int) $currentNextActorId !== $this->expectedActorUserId
                || $currentTurnEndsAt !== $this->expectedTurnEndsAt
                || (int) $currentTurnNumber !== $this->expectedTurnNumber
            ) {
                return;
            }

            $now = Carbon::now()->utc();
            $expiresAt = Carbon::parse($currentTurnEndsAt)->utc();

            if ($now->lt($expiresAt)) {
                return;
            }

            $opponentId = $battle->player1_id === $currentNextActorId
                ? $battle->player2_id
                : $battle->player1_id;

            $turnNumber = $turnNumberService->nextTurnNumber($battle);

            $logEntry = [
                'turn' => $turnNumber,
                'actor_user_id' => $currentNextActorId,
                'action' => ['type' => 'timeout'],
                'events' => [
                    ['type' => 'timeout'],
                ],
            ];

            $state['log'][] = $logEntry;
            $state['next_actor_id'] = $opponentId;
            $state['turn'] = ((int) $currentTurnNumber) + 1;
            $turnTimerService->refresh($state);

            $battle->update([
                'meta_json' => $state,
            ]);

            $battle->setAttribute('meta_json', $state);
            $stateForBroadcast = $state;
            $winnerId = $battle->winner_user_id;
            $nextActorId = $state['next_actor_id'] ?? null;

            BattleTurn::query()->create([
                'battle_id' => $battle->id,
                'turn_number' => $turnNumber,
                'actor_user_id' => $currentNextActorId,
                'action_json' => ['type' => 'timeout'],
                'result_json' => $logEntry,
            ]);

            $shouldBroadcast = true;
        });

        if (! $shouldBroadcast || ! $battle || $stateForBroadcast === null) {
            return;
        }

        $turnTimerService->scheduleTimeoutJob($battle, $stateForBroadcast);

        broadcast(new BattleUpdated(
            battleId: $battle->id,
            state: $stateForBroadcast,
            status: 'active',
            nextActorId: $nextActorId,
            winnerUserId: $winnerId,
        ));
    }
}
