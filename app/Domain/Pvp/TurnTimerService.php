<?php

namespace App\Domain\Pvp;

use Illuminate\Support\Carbon;

class TurnTimerService
{
    public function refresh(array &$state): void
    {
        $now = Carbon::now()->utc();
        $state['turn_started_at'] = $now->toIso8601String();
        $state['turn_ends_at'] = $now->copy()->addSeconds(60)->toIso8601String();
    }
}
