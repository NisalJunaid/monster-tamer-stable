<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MonsterInstance;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $monsters = MonsterInstance::with(['species.primaryType', 'species.secondaryType', 'currentStage'])
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        return view('dashboard', [
            'user' => $user,
            'monsters' => $monsters,
        ]);
    }
}
