<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlayerMonsterResource;
use App\Models\PlayerMonster;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function setSlot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_monster_id' => ['required', 'integer'],
            'slot' => ['required', 'integer', 'min:1', 'max:6'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $monsters = DB::transaction(function () use ($validated, $user) {
            /** @var PlayerMonster $monster */
            $monster = PlayerMonster::where('user_id', $user->id)
                ->whereKey($validated['player_monster_id'])
                ->firstOrFail();

            $slot = (int) $validated['slot'];

            $existingAtSlot = PlayerMonster::where('user_id', $user->id)
                ->where('team_slot', $slot)
                ->first();

            if (! $monster->is_in_team && ! $existingAtSlot) {
                $teamCount = PlayerMonster::where('user_id', $user->id)
                    ->where('is_in_team', true)
                    ->count();

                if ($teamCount >= 6) {
                    throw ValidationException::withMessages([
                        'slot' => 'Your team already has six monsters. Remove one before adding another.',
                    ]);
                }
            }

            if ($existingAtSlot && $existingAtSlot->id !== $monster->id) {
                $existingAtSlot->forceFill([
                    'is_in_team' => false,
                    'team_slot' => null,
                ])->save();
            }

            $monster->forceFill([
                'is_in_team' => true,
                'team_slot' => $slot,
            ])->save();

            return $this->userMonsters($user);
        });

        return response()->json([
            'monsters' => PlayerMonsterResource::collection($monsters)->resolve(),
        ]);
    }

    public function clearSlot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slot' => ['required', 'integer', 'min:1', 'max:6'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $monsters = DB::transaction(function () use ($validated, $user) {
            $monster = PlayerMonster::where('user_id', $user->id)
                ->where('team_slot', $validated['slot'])
                ->first();

            if ($monster) {
                $monster->forceFill([
                    'is_in_team' => false,
                    'team_slot' => null,
                ])->save();
            }

            return $this->userMonsters($user);
        });

        return response()->json([
            'monsters' => PlayerMonsterResource::collection($monsters)->resolve(),
        ]);
    }

    protected function userMonsters(User $user)
    {
        return $user->monsters()
            ->with('species')
            ->orderByDesc('is_in_team')
            ->orderBy('team_slot')
            ->orderByDesc('id')
            ->get();
    }
}
