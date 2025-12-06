@extends('layouts.app')

@section('content')
<div class="bg-white shadow rounded p-6 text-center">
    <h1 class="text-3xl font-bold mb-3">Game Online</h1>
    <p class="text-gray-700 mb-4">Jump into the Monster Tamer world to explore encounters and battle other players.</p>
    @guest
        <div class="flex justify-center space-x-3">
            <a href="{{ route('login') }}" class="px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-500">Login</a>
            <a href="{{ route('register') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500">Register</a>
        </div>
    @else
        <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-500">Go to Dashboard</a>
    @endguest
</div>
@endsection
