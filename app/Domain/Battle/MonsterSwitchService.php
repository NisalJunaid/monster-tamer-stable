<?php

namespace App\Domain\Battle;

class MonsterSwitchService
{
    /**
     * Swap the player's active monster by validating the provided identifier against the
     * current player's monsters and updating the active slot. HP and status are left intact.
     * Expects wild-style battle state with keys: player_monsters (each entry includes player_monster_id or id),
     * player_active_monster_id, optional last_action_log, and optional turn; the provided ID should match
     * a player_monster_id/int within player_monsters.
     */
    public function switchPlayerMonster(array &$state, int $playerMonsterId, array &$log, ?int $userId = null): array
    {
        foreach ($state['player_monsters'] as $index => $monster) {
            $id = $monster['player_monster_id'] ?? $monster['id'] ?? null;

            if ($id === $playerMonsterId) {
                if (($monster['current_hp'] ?? 0) <= 0) {
                    abort(400, 'Cannot switch to a fainted monster.');
                }

                $state['player_active_monster_id'] = $id;

                if (array_key_exists('last_action_log', $state)) {
                    $state['last_action_log'][] = [
                        'actor' => 'player',
                        'type' => 'switch',
                        'target_id' => $id,
                    ];
                    $log[] = end($state['last_action_log']);
                }

                if (array_key_exists('turn', $state)) {
                    // Swapping in the PvP UI layer bumps the in-memory turn
                    // counter for local state sequencing; database turn
                    // numbers are derived when persisting the swap.
                    $state['turn'] = ($state['turn'] ?? 0) + 1;
                }

                return [
                    'id' => $id,
                    'index' => $index,
                ];
            }
        }

        \Log::info('PvP switch debug', [
            'user_id' => $userId,
            'requested_player_monster_id' => $playerMonsterId,
            'available_player_monster_ids' => array_map(
                fn ($m) => $m['player_monster_id'] ?? null,
                $state['player_monsters'] ?? []
            ),
        ]);

        abort(404, 'Monster not found on your team.');
    }
}
