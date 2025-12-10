@extends('layouts.app')

@php
    $payload = $initialPayload ?? [];
    $battleState = $payload['battle'] ?? [];
    $ticket = $payload['ticket'] ?? [];

    $playerMonsters = $battleState['player_monsters'] ?? [];
    $activeId = $battleState['player_active_monster_id'] ?? null;
    $activeMonster = collect($playerMonsters)->firstWhere('id', $activeId) ?? $playerMonsters[0] ?? null;
    $opponent = $battleState['wild'] ?? null;
    $opponentMonsters = $battleState['opponent_monsters'] ?? [];
    $opponentAliveCount = collect($opponentMonsters)->filter(fn ($monster) => ($monster['current_hp'] ?? 0) > 0)->count();

    $hpPercent = $activeMonster ? max(0, min(100, (int) floor(($activeMonster['current_hp'] / max(1, $activeMonster['max_hp'])) * 100))) : 0;
    $opponentHpPercent = $opponent ? max(0, min(100, (int) floor(($opponent['current_hp'] / max(1, $opponent['max_hp'])) * 100))) : 0;
@endphp

@section('content')
<style>
    .team-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 9999px;
        background-color: #94a3b8;
    }

    .team-dot + .team-dot {
        margin-left: 6px;
    }
</style>
<div id="wild-battle-page"
     class="space-y-4"
     data-move-url="{{ route('pvp.battles.wild.move', $battle) }}"
     data-switch-url="{{ route('pvp.battles.wild.switch', $battle) }}"
     data-tame-url=""
     data-run-url="{{ route('pvp.battles.wild.run', $battle) }}"
     data-encounters-url="{{ route('pvp.index') }}"
     data-user-id="{{ $viewer->id }}"
     data-ticket-id="{{ $battle->id }}"
     data-mode="pvp">

    <script type="application/json" data-wild-battle-state>
        {!! json_encode(array_merge($payload, ['mode' => 'pvp'])) !!}
    </script>

    <div class="bg-white shadow rounded-xl p-5 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">PvP Battle</h1>
            <p class="text-gray-600">Battle #{{ $battle->id }} • Turn <span data-turn>{{ $battleState['turn'] ?? 1 }}</span></p>
            <p class="text-sm text-gray-500" data-battle-status>State: {{ ($battleState['active'] ?? false) ? 'Active' : 'Resolved' }}</p>
        </div>
        <button class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500" data-back-button>
            Back to PvP lobby
        </button>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div class="bg-slate-900 text-white rounded-xl p-5 shadow-inner" data-panel-player>
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-300">You</p>
                    <h2 class="text-xl font-bold" data-player-name>{{ $activeMonster['name'] ?? 'Unknown' }}</h2>
                    <p class="text-sm text-slate-300" data-player-level>Level {{ $activeMonster['level'] ?? '?' }}</p>
                    <p class="text-amber-300 text-sm" data-player-status>Ready</p>
                </div>
                <div class="w-48">
                    <div class="text-right text-xs text-slate-200" data-player-hp-text>
                        HP {{ $activeMonster['current_hp'] ?? 0 }} / {{ $activeMonster['max_hp'] ?? 0 }}
                    </div>
                    <div class="w-full bg-slate-700 rounded-full h-3">
                        <div class="h-3 rounded-full bg-emerald-400" data-player-hp-bar style="width: {{ $hpPercent }}%"></div>
                    </div>
                </div>
            </div>
            <div class="mt-3 space-y-2 text-sm" data-player-team>
                @foreach($playerMonsters as $monster)
                    <div class="flex items-center justify-between bg-slate-800/60 rounded px-3 py-2">
                        <span>{{ $monster['name'] }} (Lv {{ $monster['level'] }})</span>
                        <span class="text-slate-200">HP {{ $monster['current_hp'] }} / {{ $monster['max_hp'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white border rounded-xl p-5" data-panel-wild>
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">Opponent</p>
                    <h2 class="text-xl font-bold" data-wild-name>{{ $opponent['name'] ?? ($ticket['species']['name'] ?? 'Unknown') }}</h2>
                    <p class="text-sm text-gray-600" data-wild-level>Level {{ $opponent['level'] ?? ($ticket['species']['level'] ?? '?') }}</p>
                    <p class="text-amber-700 text-sm" data-wild-status>Alert</p>
                </div>
                <div class="w-48">
                    <div class="text-right text-xs text-gray-600" data-wild-hp-text>
                        HP {{ $opponent['current_hp'] ?? 0 }} / {{ $opponent['max_hp'] ?? 0 }}
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="h-3 rounded-full bg-rose-400" data-wild-hp-bar style="width: {{ $opponentHpPercent }}%"></div>
                    </div>
                </div>
            </div>
            <div class="mt-2 text-sm text-gray-600" data-wild-description>
                PvP opponent in battle.
            </div>
            <div class="mt-3 flex items-center gap-2" data-opponent-team-dots>
                <span class="text-xs uppercase tracking-wide text-gray-500">Team</span>
                <div class="flex items-center" data-opponent-team-dots-list>
                    @if($opponentAliveCount > 0)
                        @for($i = 0; $i < $opponentAliveCount; $i++)
                            <span class="team-dot"></span>
                        @endfor
                    @else
                        <span class="text-xs text-gray-500">No conscious monsters</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-xl p-5 space-y-4" data-action-menu>
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">Actions</h3>
            <span class="text-sm text-gray-600" data-turn-indicator>{{ ($battleState['active'] ?? false) ? 'Choose your move' : 'Battle resolved' }}</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
            <button type="button" class="px-3 py-2 rounded bg-slate-900 text-white hover:bg-slate-800" data-action-tab="move">Move</button>
            <button type="button" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300" data-action-tab="bag">Bag</button>
            <button type="button" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300" data-action-tab="switch">Switch</button>
            <button type="button" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300" data-action-tab="run">Run</button>
        </div>
        <div class="border rounded-lg p-4 bg-gray-50" data-action-panel="move">
            <p class="text-sm text-gray-600 mb-3">Select a move:</p>
            <div class="grid md:grid-cols-2 gap-3" data-move-list>
                <button class="px-3 py-3 rounded-lg border border-gray-200 bg-white hover:border-emerald-400" data-move-style="monster">Monster Technique</button>
                <button class="px-3 py-3 rounded-lg border border-gray-200 bg-white hover:border-emerald-400" data-move-style="martial">Martial Arts</button>
            </div>
        </div>
        <div class="border rounded-lg p-4 bg-gray-50 hidden" data-action-panel="bag">
            <p class="text-sm text-gray-600">Your bag is empty. No usable items.</p>
        </div>
        <div class="border rounded-lg p-4 bg-gray-50 hidden" data-action-panel="switch">
            <p class="text-sm text-gray-600 mb-3">Switch to another teammate:</p>
            <div class="grid md:grid-cols-2 gap-2" data-switch-list></div>
        </div>
        <div class="border rounded-lg p-4 bg-gray-50 hidden" data-action-panel="run">
            <p class="text-sm text-gray-600 mb-3">Forfeit the battle.</p>
            <button class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-500" data-action-run>Concede</button>
        </div>
        <p class="text-sm text-gray-500" data-action-status></p>
    </div>

    <div class="bg-white shadow rounded-xl p-5" data-battle-log>
        <h3 class="text-lg font-semibold mb-3">Battle Log</h3>
        <div class="space-y-2" data-log-entries>
            @forelse(array_reverse($battleState['last_action_log'] ?? []) as $entry)
                <div class="border rounded p-3 bg-gray-50">
                    <p class="font-semibold">{{ ucfirst($entry['actor'] ?? 'unknown') }} used {{ $entry['type'] ?? 'action' }}</p>
                    <p class="text-sm text-gray-600">@json($entry)</p>
                </div>
            @empty
                <p class="text-gray-600">No actions yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
