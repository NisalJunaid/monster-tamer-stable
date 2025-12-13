<?php 

namespace App\Services;

use App\Models\Item;
use App\Models\Move;
use App\Models\PlayerMonster;
use App\Models\MonsterLearnedMove;

class ItemEffectService
{
    public function apply(Item $item, PlayerMonster $monster): array
    {
        return match ($item->effect_type) {
            'heal_hp'   => $this->healHp($item, $monster),
            'teach_move'=> $this->teachMove($item, $monster),
            default     => [
                'status' => 'failed',
                'message' => 'Unknown item effect.',
                'changes' => [],
            ],
        };
    }

    /* ---------------- HEAL ---------------- */

    private function healHp(Item $item, PlayerMonster $monster): array
    {
        $amount = (int)($item->effect_payload['amount'] ?? 0);
        if ($amount <= 0) {
            return ['status'=>'failed','message'=>'Invalid heal amount.','changes'=>[]];
        }

        if ($monster->current_hp >= $monster->max_hp) {
            return ['status'=>'no_effect','message'=>'HP already full.','changes'=>[]];
        }

        $before = $monster->current_hp;
        $monster->current_hp = min($monster->max_hp, $before + $amount);
        $monster->save();

        return [
            'status' => 'success',
            'message'=> "Healed {$amount} HP.",
            'changes'=> [
                'hp_before'=>$before,
                'hp_after'=>$monster->current_hp,
            ],
        ];
    }

    /* ---------------- TEACH MOVE ---------------- */

    private function teachMove(Item $item, PlayerMonster $monster): array
    {
        $moveId = (int)($item->effect_payload['move_id'] ?? 0);
        if ($moveId <= 0) {
            return ['status'=>'failed','message'=>'Invalid move.','changes'=>[]];
        }

        $move = Move::find($moveId);
        if (!$move) {
            return ['status'=>'failed','message'=>'Move not found.','changes'=>[]];
        }

        $alreadyKnown = MonsterLearnedMove::where('player_monster_id', $monster->id)
            ->where('move_id', $moveId)
            ->exists();

        if ($alreadyKnown) {
            return [
                'status' => 'no_effect',
                'message'=> 'Monster already knows this move.',
                'changes'=> [],
            ];
        }

        MonsterLearnedMove::create([
            'player_monster_id' => $monster->id,
            'move_id'           => $moveId,
            'learned_method'    => 'item',
        ]);

        return [
            'status' => 'success',
            'message'=> "Learned {$move->name}.",
            'changes'=> [
                'learned_move_id' => $moveId,
                'learned_method' => 'item',
            ],
        ];
    }
}
