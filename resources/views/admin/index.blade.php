@extends('layouts.app')

@section('content')
<div class="bg-white shadow rounded p-6 space-y-3">
    <h1 class="text-2xl font-bold">Admin Panel</h1>
    <p class="text-gray-700">Manage encounter zones and spawn tables.</p>
    <div class="space-x-3">
        <a href="{{ route('admin.zones.map') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500">Zone Map</a>
    </div>
</div>
@endsection
