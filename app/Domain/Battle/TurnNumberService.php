<?php

namespace App\Domain\Battle;

use App\Models\Battle;
use App\Models\BattleTurn;

class TurnNumberService
{
    public function nextTurnNumber(Battle $battle): int
    {
        $current = BattleTurn::query()
            ->where('battle_id', $battle->id)
            ->max('turn_number');

        return ((int) $current) + 1;
    }
}
