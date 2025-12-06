@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="bg-white shadow rounded p-6">
        <h1 class="text-2xl font-bold mb-3">PvP Queue</h1>
        <p class="text-gray-600 mb-4">Queue for a battle. Matchmaking runs every minute.</p>
        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('pvp.queue') }}">
                @csrf
                <input type="hidden" name="mode" value="ranked" />
                <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500">Queue Ranked</button>
            </form>
            <form method="POST" action="{{ route('pvp.queue') }}">
                @csrf
                <input type="hidden" name="mode" value="casual" />
                <button class="px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-500">Queue Casual</button>
            </form>
            <form method="POST" action="{{ route('pvp.dequeue') }}">
                @csrf
                @method('DELETE')
                <button class="px-4 py-2 bg-gray-200 text-gray-800 rounded border hover:bg-gray-300">Dequeue</button>
            </form>
        </div>
        <div class="mt-4 p-3 bg-gray-50 rounded border">
            <p class="font-semibold">Queue Status:</p>
            @if($queueEntry)
                <p class="text-gray-700">Queued for {{ $queueEntry->mode }} since {{ $queueEntry->queued_at }}.</p>
            @else
                <p class="text-gray-600">Not currently queued.</p>
            @endif
        </div>
    </div>

    <div class="bg-white shadow rounded p-6">
        <h2 class="text-xl font-semibold mb-2">Recent Battle</h2>
        @if($latestBattle)
            <p class="text-gray-700">Battle #{{ $latestBattle->id }} ({{ $latestBattle->status }})</p>
            <a class="text-teal-600 underline" href="{{ route('battles.show', $latestBattle) }}">Open Battle</a>
        @else
            <p class="text-gray-600">No battles yet. Queue up to start one.</p>
        @endif
    </div>
</div>
@endsection
