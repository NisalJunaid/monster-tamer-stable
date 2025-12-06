@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Spawn Entries for {{ $zone->name }}</h1>
            <p class="text-gray-600">Manage which species appear in this zone.</p>
        </div>
        <a href="{{ route('admin.zones.map') }}" class="text-teal-600 underline">&larr; Back to Zones</a>
    </div>

    @if(session('status'))
        <div class="p-3 bg-green-100 border border-green-200 rounded text-green-800">{{ session('status') }}</div>
    @endif

    <div class="bg-white shadow rounded p-4">
        <h2 class="text-xl font-semibold mb-3">Create Entry</h2>
        <form method="POST" action="{{ route('admin.zones.spawns.store', $zone) }}" class="space-y-2">
            @csrf
            <div class="grid md:grid-cols-5 gap-3">
                <label class="text-sm">Species ID <input type="number" name="species_id" required class="w-full border rounded px-2 py-1"></label>
                <label class="text-sm">Weight <input type="number" name="weight" value="1" required class="w-full border rounded px-2 py-1"></label>
                <label class="text-sm">Min Lv <input type="number" name="min_level" value="1" required class="w-full border rounded px-2 py-1"></label>
                <label class="text-sm">Max Lv <input type="number" name="max_level" value="5" required class="w-full border rounded px-2 py-1"></label>
                <label class="text-sm">Rarity <input type="text" name="rarity_tier" placeholder="common" class="w-full border rounded px-2 py-1"></label>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Add Entry</button>
        </form>
    </div>

    <div class="bg-white shadow rounded p-4">
        <h2 class="text-xl font-semibold mb-3">Existing Entries</h2>
        <div class="overflow-x-auto">
            <table class="w-full border border-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="border p-2">ID</th>
                        <th class="border p-2">Species</th>
                        <th class="border p-2">Weight</th>
                        <th class="border p-2">Level Range</th>
                        <th class="border p-2">Rarity</th>
                        <th class="border p-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($zone->spawnEntries as $entry)
                        <tr>
                            <td class="border p-2">{{ $entry->id }}</td>
                            <td class="border p-2">{{ $entry->species->name ?? ('#'.$entry->species_id) }}</td>
                            <td class="border p-2">{{ $entry->weight }}</td>
                            <td class="border p-2">{{ $entry->min_level }} - {{ $entry->max_level }}</td>
                            <td class="border p-2">{{ $entry->rarity_tier ?? 'n/a' }}</td>
                            <td class="border p-2">
                                <div class="space-y-2">
                                    <form method="POST" action="{{ route('admin.zones.spawns.update', [$zone, $entry]) }}" class="space-y-2">
                                        @csrf
                                    @method('PUT')
                                        <div class="grid md:grid-cols-5 gap-2 mb-2">
                                            <input type="number" name="species_id" value="{{ $entry->species_id }}" required class="border rounded px-2 py-1">
                                            <input type="number" name="weight" value="{{ $entry->weight }}" required class="border rounded px-2 py-1">
                                            <input type="number" name="min_level" value="{{ $entry->min_level }}" required class="border rounded px-2 py-1">
                                            <input type="number" name="max_level" value="{{ $entry->max_level }}" required class="border rounded px-2 py-1">
                                            <input type="text" name="rarity_tier" value="{{ $entry->rarity_tier }}" class="border rounded px-2 py-1">
                                        </div>
                                        <button type="submit" class="px-3 py-1 bg-teal-600 text-white rounded">Update</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.zones.spawns.destroy', [$zone, $entry]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-3 text-center text-gray-600">No entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
