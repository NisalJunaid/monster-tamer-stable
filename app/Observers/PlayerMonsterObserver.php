<?php

namespace App\Observers;

use App\Events\UserMonstersUpdated;
use App\Models\PlayerMonster;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlayerMonsterObserver
{
    public function created(PlayerMonster $playerMonster): void
    {
        $this->broadcastForUser($playerMonster->user_id);
    }

    public function updated(PlayerMonster $playerMonster): void
    {
        $this->broadcastForUser($playerMonster->user_id);
    }

    public function deleted(PlayerMonster $playerMonster): void
    {
        $this->broadcastForUser($playerMonster->user_id);
    }

    protected function broadcastForUser(int $userId): void
    {
        $user = User::find($userId);

        if (! $user) {
            return;
        }

        DB::afterCommit(function () use ($user) {
            $monsters = $this->userMonsters($user);

            broadcast(new UserMonstersUpdated($user, $monsters));
        });
    }

    protected function userMonsters(User $user): Collection
    {
        return $user->monsters()
            ->with('species')
            ->orderByDesc('is_in_team')
            ->orderBy('team_slot')
            ->orderByDesc('id')
            ->get();
    }
}
