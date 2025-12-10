<?php

namespace App\Domain\Battle;

class MonsterSwitchService
{
    /**
     * Swap the player's active monster by validating the provided identifier against the
     * current player's monsters and updating the active slot. HP and status are left intact.
     */
    public function switchPlayerMonster(array &$state, int $playerMonsterId, array &$log, ?int $userId = null): void
    {
        foreach ($state['player_monsters'] as $monster) {
            $id = $monster['player_monster_id'] ?? $monster['id'] ?? null;

            if ($id === $playerMonsterId) {
                if (($monster['current_hp'] ?? 0) <= 0) {
                    abort(400, 'Cannot switch to a fainted monster.');
                }

                $state['player_active_monster_id'] = $id;
                $state['last_action_log'][] = [
                    'actor' => 'player',
                    'type' => 'switch',
                    'target_id' => $id,
                ];
                $log[] = end($state['last_action_log']);
                $state['turn']++;

                return;
            }
        }

        abort(404, 'Monster not found on your team.');
    }
}
