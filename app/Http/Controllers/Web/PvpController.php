<?php

namespace App\Http\Controllers\Web;

use App\Domain\Pvp\PvpRankingService;
use App\Http\Controllers\Controller;
use App\Models\Battle;
use App\Models\MatchmakingQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PvpController extends Controller
{
    public function __construct(private readonly PvpRankingService $rankingService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $queueEntry = MatchmakingQueue::where('user_id', $user->id)->first();
        $latestBattle = Battle::where(function ($query) use ($user) {
            $query->where('player1_id', $user->id)->orWhere('player2_id', $user->id);
        })->latest('id')->first();

        return view('pvp.index', [
            'queueEntry' => $queueEntry,
            'latestBattle' => $latestBattle,
        ]);
    }

    public function queue(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => 'required|in:ranked,casual',
        ]);

        $user = $request->user();
        $this->rankingService->ensureProfile($user->id);

        MatchmakingQueue::updateOrCreate(
            ['user_id' => $user->id],
            [
                'mode' => $data['mode'],
                'queued_at' => now(),
            ],
        );

        return back()->with('status', "Queued for {$data['mode']} matchmaking. Matchmaking runs every minute.");
    }

    public function dequeue(Request $request): RedirectResponse
    {
        MatchmakingQueue::where('user_id', $request->user()->id)->delete();

        return back()->with('status', 'Removed from matchmaking queue.');
    }
}
