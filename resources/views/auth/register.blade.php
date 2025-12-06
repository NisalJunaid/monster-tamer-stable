@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white shadow rounded p-6">
    <h1 class="text-2xl font-bold mb-4">Register</h1>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full border-gray-300 rounded" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full border-gray-300 rounded" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" name="password" required class="mt-1 w-full border-gray-300 rounded" />
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
            <input type="password" name="password_confirmation" required class="mt-1 w-full border-gray-300 rounded" />
        </div>
        <button class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-500">Create account</button>
    </form>
</div>
@endsection
