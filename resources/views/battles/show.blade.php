@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="bg-white shadow rounded p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Battle #{{ $battle->id }}</h1>
                <p class="text-gray-600">Status: {{ ucfirst($battle->status) }} | Seed: {{ $battle->seed }}</p>
                <p class="text-sm text-gray-500">Players: {{ $battle->player1?->name }} vs {{ $battle->player2?->name }}</p>
                @if($battle->winner_user_id)
                    <p class="text-green-700 font-semibold">Winner: {{ $battle->winner?->name }}</p>
                @endif
            </div>
            <div class="text-sm text-gray-600">Next actor: {{ $state['next_actor_id'] ?? 'Unknown' }}</div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        @foreach($state['participants'] ?? [] as $userId => $participant)
            @php
                $monsters = $participant['monsters'] ?? [];
                $activeIndex = $participant['active_index'] ?? 0;
                $active = $monsters[$activeIndex] ?? null;
                $bench = collect($monsters)->reject(fn($m, $index) => $index === $activeIndex);
            @endphp
            <div class="bg-white shadow rounded p-4">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-semibold">Player {{ $userId === $battle->player1_id ? '1' : '2' }}</h2>
                    <span class="text-sm text-gray-500">Active: {{ $active['name'] ?? 'N/A' }}</span>
                </div>
                @if($active)
                    <p class="text-gray-700">HP: {{ $active['current_hp'] }} / {{ $active['max_hp'] }}</p>
                    <p class="text-gray-700">Types: {{ implode(', ', $active['type_names'] ?? []) ?: 'Unknown' }}</p>
                    @if($active['status'])
                        <p class="text-yellow-700">Status: {{ $active['status']['name'] }} ({{ $active['status']['turns'] ?? '?' }} turns)</p>
                    @endif
                    <div class="mt-3">
                        <p class="font-semibold mb-1">Moves</p>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            @foreach($active['moves'] as $move)
                                <div class="border rounded p-2">
                                    <p class="font-medium">Slot {{ $move['slot'] }}: {{ $move['name'] }}</p>
                                    <p class="text-gray-600">{{ ucfirst($move['category']) }} | {{ $move['type'] }} | Power {{ $move['power'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <p class="text-gray-600">No active monster.</p>
                @endif

                @if(($state['next_actor_id'] ?? null) === auth()->id() && $battle->status === 'active' && $active)
                    <div class="mt-4 space-y-3">
                        <form method="POST" action="{{ route('battles.act', $battle) }}" class="flex items-center space-x-2">
                            @csrf
                            <input type="hidden" name="type" value="move">
                            <select name="slot" class="border-gray-300 rounded">
                                @foreach($active['moves'] as $move)
                                    <option value="{{ $move['slot'] }}">Use {{ $move['name'] }} (Slot {{ $move['slot'] }})</option>
                                @endforeach
                            </select>
                            <button class="px-3 py-2 bg-teal-600 text-white rounded hover:bg-teal-500">Submit Move</button>
                        </form>
                        @if($bench->isNotEmpty())
                            <form method="POST" action="{{ route('battles.act', $battle) }}" class="flex items-center space-x-2">
                                @csrf
                                <input type="hidden" name="type" value="swap">
                                <select name="monster_instance_id" class="border-gray-300 rounded">
                                    @foreach($bench as $monster)
                                        <option value="{{ $monster['id'] }}">Swap to {{ $monster['name'] }}</option>
                                    @endforeach
                                </select>
                                <button class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-500">Swap</button>
                            </form>
                        @endif
                    </div>
                @elseif($battle->status === 'active')
                    <p class="mt-3 text-sm text-gray-500">Waiting for opponent action...</p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="bg-white shadow rounded p-6">
        <h2 class="text-xl font-semibold mb-3">Turn Log</h2>
        @if(($state['log'] ?? []) === [])
            <p class="text-gray-600">No turns recorded yet.</p>
        @else
            <div class="space-y-3 text-sm">
                @foreach($state['log'] as $entry)
                    <div class="border rounded p-3">
                        <p class="font-semibold">Turn {{ $entry['turn'] }} by User {{ $entry['actor_user_id'] }}</p>
                        <p class="text-gray-700">Action: {{ $entry['action']['type'] }} @if(($entry['action']['type'] ?? '') === 'move') (Slot {{ $entry['action']['slot'] }}) @endif</p>
                        <ul class="list-disc list-inside text-gray-600">
                            @foreach($entry['events'] as $event)
                                <li>{{ ucfirst($event['type']) }} - {{ json_encode($event) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
