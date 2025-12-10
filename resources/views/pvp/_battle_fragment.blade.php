<div id="matchmaking-panel"
     class="space-y-4"
     data-user-id="{{ auth()->id() }}"
     data-battle-fragment-url="{{ route('pvp.battle_fragment', ['battle' => '__BATTLE_ID__']) }}"
     data-battle-url="{{ route('pvp.battles.wild', ['battle' => '__BATTLE_ID__']) }}"
     data-active-battle-id="{{ $battle->id }}">
    <div class="bg-white shadow rounded-xl p-4 flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-600">Active PvP battle</p>
            <p class="text-lg font-semibold">Battle #{{ $battle->id }} is in progress.</p>
        </div>
        <div class="flex items-center gap-3">
            @include('partials.live_badge')
            <div class="flex items-center gap-2">
                <a class="px-3 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500" href="{{ route('pvp.battles.wild', $battle) }}">Open battle UI</a>
                <a class="px-3 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100" href="{{ route('pvp.index') }}">Back to lobby</a>
            </div>
        </div>
    </div>

    <div class="hidden p-3 bg-indigo-50 text-indigo-800 border border-indigo-200 rounded" data-battle-finished-banner>
        Battle finished! Returning to lobby...
    </div>

    <div class="bg-white border rounded-xl p-4 text-gray-700 space-y-2" data-battle-live>
        <p class="font-semibold">Switching to new PvP battle view...</p>
        <p class="text-sm">If you are not redirected automatically, use the button above to open the wild-style interface.</p>
    </div>
</div>
