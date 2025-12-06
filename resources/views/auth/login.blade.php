@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white shadow rounded p-6">
    <h1 class="text-2xl font-bold mb-4">Login</h1>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full border-gray-300 rounded" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" name="password" required class="mt-1 w-full border-gray-300 rounded" />
        </div>
        <button class="w-full bg-teal-600 text-white py-2 rounded hover:bg-teal-500">Login</button>
    </form>
</div>
@endsection
