<?php

namespace App\Domain\Pvp;

use App\Jobs\PvpTurnTimeoutJob;
use App\Models\Battle;
use Illuminate\Support\Carbon;

class TurnTimerService
{
    public function refresh(array &$state): void
    {
        $now = Carbon::now()->utc();
        $state['turn_started_at'] = $now->toIso8601String();
        $state['turn_ends_at'] = $now->copy()->addSeconds(60)->toIso8601String();
    }

    public function scheduleTimeoutJob(Battle $battle, array $state): void
    {
        $nextActorId = $state['next_actor_id'] ?? null;
        $turnEndsAt = $state['turn_ends_at'] ?? null;
        $turnNumber = $state['turn'] ?? null;

        if ($nextActorId === null || $turnEndsAt === null || $turnNumber === null) {
            return;
        }

        $endsAt = Carbon::parse($turnEndsAt)->utc();

        PvpTurnTimeoutJob::dispatch(
            battleId: $battle->id,
            expectedActorUserId: (int) $nextActorId,
            expectedTurnEndsAt: $turnEndsAt,
            expectedTurnNumber: (int) $turnNumber,
        )->delay($endsAt);
    }
}
