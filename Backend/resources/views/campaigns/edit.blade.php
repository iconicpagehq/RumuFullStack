@extends('layouts.app')

@section('title', 'Create Campaign - Rumu App')

@section('content')
    <div class="min-h-screen bg-gray-50">
        @if(Auth::user()->role === 'admin')
            @include('admin.partials.sidebar')
        @else
            @include('realtor.partials.sidebar')
        @endif

        <!-- Main Content -->
        <main class="lg:ml-64 min-h-screen">
            <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-10">
                <div class="px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 sm:space-x-4">
                            <button onclick="toggleMobileMenu()"
                                class="lg:hidden text-gray-600 hover:text-primary p-2 -ml-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <div>
                                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800">Edit Campaign</h1>
                                <p class="text-gray-500 mt-1 text-sm sm:text-base hidden sm:block">Update campaign details
                                </p>
                            </div>
                        </div>
                        @include('realtor.partials.user-dropdown')
                    </div>
                </div>
            </header>

            <div class="p-4 sm:p-6 lg:p-8">
                @include('realtor.partials.subscription-suspended-banner')

                <div class="max-w-3xl mx-auto">
                    <!-- Create Form -->
                    <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                        <form method="POST" action="{{ route('campaigns.update', $campaign) }}">
                            @csrf
                            @method('PUT')

                            <!-- Campaign Name -->
                            <div class="mb-6">
                                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Campaign Name
                                    *</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $campaign->name) }}" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition @error('name') border-red-500 @enderror"
                                    placeholder="e.g., Q1 2024 Lead Generation">
                                @error('name')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="mb-6">
                                <label for="description"
                                    class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                                <textarea name="description" id="description" rows="4"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition @error('description') border-red-500 @enderror"
                                    placeholder="Describe the purpose and goals of this campaign...">{{ old('description', $campaign->description) }}</textarea>
                                @error('description')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="mb-6">
                                <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status *</label>
                                <select name="status" id="status" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition @error('status') border-red-500 @enderror">
                                    <option value="active" {{ old('status', $campaign->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="paused" {{ old('status', $campaign->status) === 'paused' ? 'selected' : '' }}>Paused</option>
                                    <option value="completed" {{ old('status', $campaign->status) === 'completed' ? 'selected' : '' }}>Completed
                                    </option>
                                </select>
                                @error('status')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                                <button type="submit"
                                    class="flex-1 bg-primary hover:bg-primary/90 text-white font-semibold py-3 px-6 rounded-lg transition flex items-center justify-center space-x-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Update Campaign</span>
                                </button>
                                <a href="{{ route('campaigns.index') }}"
                                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-6 rounded-lg transition text-center">
                                    Cancel
                                </a>
                            </div>
                        </form>

                        <!-- Delete Section -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-sm font-semibold text-red-600 mb-3">Danger Zone</h3>
                            <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}" 
                                onsubmit="return confirm('Are you sure you want to delete this campaign? All leads and notes will be permanently deleted.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition flex items-center justify-center space-x-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    <span>Delete Campaign</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
@endsection