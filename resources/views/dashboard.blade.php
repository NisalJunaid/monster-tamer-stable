@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="bg-white shadow rounded p-6">
        <h1 class="text-2xl font-bold">Welcome, {{ $user->name }}</h1>
        <p class="text-gray-700">Use the navigation to explore encounters or PvP.</p>
    </div>

    <div class="bg-white shadow rounded p-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xl font-semibold">Your Monsters</h2>
            <span class="text-sm text-gray-500">{{ $monsters->count() }} owned</span>
        </div>
        @if($monsters->isEmpty())
            <p class="text-gray-600">No monsters captured yet. Resolve encounters to grow your party.</p>
        @else
            <div class="grid md:grid-cols-2 gap-4">
                @foreach($monsters as $monster)
                    <div class="border rounded p-3">
                        <div class="flex justify-between mb-1">
                            <div>
                                <p class="font-semibold">{{ $monster->nickname ?: $monster->currentStage->name }}</p>
                                <p class="text-sm text-gray-600">Lv {{ $monster->level }}</p>
                            </div>
                            <div class="text-sm text-gray-500">Types: {{ implode(', ', array_filter([$monster->species->primaryType?->name, $monster->species->secondaryType?->name])) ?: 'Unknown' }}</div>
                        </div>
                        <p class="text-sm text-gray-600">Stats: HP {{ $monster->currentStage->hp }}, ATK {{ $monster->currentStage->attack }}, DEF {{ $monster->currentStage->defense }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
