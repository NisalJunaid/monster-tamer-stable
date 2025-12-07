<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlayerMonsterResource;
use App\Models\PlayerMonster;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $monsters = PlayerMonster::with('species')
            ->where('user_id', $user->id)
            ->orderByDesc('is_in_team')
            ->orderBy('team_slot')
            ->orderByDesc('id')
            ->get();

        return view('dashboard', [
            'user' => $user,
            'monsters' => $monsters,
            'monsterPayload' => PlayerMonsterResource::collection($monsters)->resolve(),
        ]);
    }
}
