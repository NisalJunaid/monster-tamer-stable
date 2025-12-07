@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="bg-white shadow rounded p-6">
        <h1 class="text-2xl font-bold">Welcome, {{ $user->name }}</h1>
        <p class="text-gray-700">Use the navigation to explore encounters or PvP.</p>
    </div>

    @php
        $teamSlots = [];
        foreach ($monsters as $monster) {
            if ($monster->team_slot) {
                $teamSlots[$monster->team_slot] = $monster;
            }
        }
    @endphp

    <div
        class="bg-white shadow rounded p-6"
        id="monster-dashboard"
        data-user-id="{{ $user->id }}"
        data-monsters='@json($monsterPayload)'
        data-team-set-url="{{ route('team.set') }}"
        data-team-clear-url="{{ route('team.clear') }}"
    >
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold">My Monsters</h2>
                <p class="text-gray-500 text-sm">Assign up to six monsters to your active team.</p>
            </div>
            <div class="text-sm text-gray-600"><span data-monster-count>{{ $monsters->count() }}</span> owned</div>
        </div>

        <div class="mb-4">
            <p class="text-sm text-green-700 hidden" data-team-status></p>
        </div>

        <div class="mb-6">
            <h3 class="font-semibold text-lg mb-3">Team Slots</h3>
            <div class="grid md:grid-cols-3 gap-3" data-team-slots>
                @for($i = 1; $i <= 6; $i++)
                    @php $slotMonster = $teamSlots[$i] ?? null; @endphp
                    <div class="border rounded p-3 flex flex-col gap-2" data-slot-card data-slot="{{ $i }}">
                        <div class="flex items-center justify-between">
                            <div class="font-semibold">Slot {{ $i }}</div>
                            @if($slotMonster)
                                <span class="text-xs text-teal-700 font-semibold">In Team</span>
                            @else
                                <span class="text-xs text-gray-500">Empty</span>
                            @endif
                        </div>
                        @if($slotMonster)
                            <div class="text-sm text-gray-700">
                                <p class="font-semibold">{{ $slotMonster->nickname ?: $slotMonster->species->name }}</p>
                                <p>Species: {{ $slotMonster->species->name }}</p>
                                <p>Level {{ $slotMonster->level }} • HP {{ $slotMonster->current_hp }}/{{ $slotMonster->max_hp }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="px-3 py-1 text-sm bg-gray-200 hover:bg-gray-300 rounded"
                                    data-action="clear-slot"
                                    data-slot="{{ $i }}"
                                >
                                    Clear Slot
                                </button>
                            </div>
                        @else
                            <p class="text-sm text-gray-500">Choose a monster below to fill this slot.</p>
                        @endif
                    </div>
                @endfor
            </div>
        </div>

        <div class="border-t pt-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-lg">Owned Monsters</h3>
                <span class="text-sm text-gray-500">Add or remove monsters from your team.</span>
            </div>

            <div class="grid md:grid-cols-2 gap-4" data-monster-list>
                @forelse($monsters as $monster)
                    <div class="border rounded p-3 flex flex-col gap-2" data-monster-card data-monster-id="{{ $monster->id }}">
                        <div class="flex justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $monster->nickname ?: $monster->species->name }}</p>
                                <p class="text-sm text-gray-600">Species: {{ $monster->species->name }}</p>
                            </div>
                            <div class="text-sm text-gray-500">
                                @if($monster->team_slot)
                                    Slot {{ $monster->team_slot }}
                                @else
                                    Not in team
                                @endif
                            </div>
                        </div>
                        <p class="text-sm text-gray-700">Level {{ $monster->level }} • HP {{ $monster->current_hp }}/{{ $monster->max_hp }}</p>

                        <div class="flex flex-wrap gap-2 items-center">
                            <label class="text-sm text-gray-600" for="monster-slot-{{ $monster->id }}">Team Slot</label>
                            <select
                                id="monster-slot-{{ $monster->id }}"
                                class="border rounded px-2 py-1 text-sm"
                                data-slot-select
                                data-monster-id="{{ $monster->id }}"
                            >
                                @for($i = 1; $i <= 6; $i++)
                                    <option value="{{ $i }}" @selected($monster->team_slot === $i)>Slot {{ $i }}</option>
                                @endfor
                            </select>
                            <button
                                type="button"
                                class="px-3 py-1 bg-teal-600 hover:bg-teal-500 text-white rounded text-sm"
                                data-action="assign-slot"
                                data-monster-id="{{ $monster->id }}"
                            >
                                {{ $monster->team_slot ? 'Update Slot' : 'Add to Team' }}
                            </button>
                            @if($monster->team_slot)
                                <button
                                    type="button"
                                    class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded text-sm"
                                    data-action="remove-from-team"
                                    data-monster-id="{{ $monster->id }}"
                                    data-slot="{{ $monster->team_slot }}"
                                >
                                    Remove
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-gray-600">No monsters captured yet. Resolve encounters to grow your party.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
