@extends('layouts.app')

@section('title', 'New Inventory Project - Rumu App')

@section('content')
<div class="min-h-screen bg-gray-50">
    @include('realtor.partials.sidebar')
    <main class="lg:ml-64 min-h-screen">
        @include('realtor.partials.header')
        <div class="p-4 sm:p-6 lg:p-8 max-w-3xl mx-auto">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Create Inventory Project</h2>
                <form method="POST" action="{{ route('inventory.projects.store') }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Project Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Location</label>
                                <input type="text" name="location" value="{{ old('location') }}" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">City</label>
                                <input type="text" name="city" value="{{ old('city') }}" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="3" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">{{ old('description') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button type="submit" class="bg-primary text-white font-semibold py-2 px-6 rounded-lg">Create Project</button>
                        <a href="{{ route('inventory.projects.index') }}" class="py-2 px-6 text-gray-600">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
@endsection
