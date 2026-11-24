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
                                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800">Create Campaign</h1>
                                <p class="text-gray-500 mt-1 text-sm sm:text-base hidden sm:block">Set up a new lead
                                    generation campaign</p>
                            </div>
                        </div>
                        @include('realtor.partials.user-dropdown')
                    </div>
                </div>
            </header>

            <div class="p-4 sm:p-6 lg:p-8">
                @include('realtor.partials.subscription-suspended-banner')

                <div class="max-w-3xl mx-auto">
                    <!-- Plan Warning (if applicable) -->
                    @if(Auth::user()->plan->isBasic())
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h3 class="text-sm font-semibold text-yellow-800">Basic Plan Limits</h3>
                                    <p class="text-sm text-yellow-700 mt-1">You can create up to
                                        {{ Auth::user()->plan->max_campaigns }} campaigns with
                                        {{ Auth::user()->plan->max_leads_per_campaign }} leads each. <a href="#"
                                            class="underline font-semibold">Upgrade to Pro</a> for unlimited campaigns and
                                        leads.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Create Form -->
                    <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                        <form method="POST" action="{{ route('campaigns.store') }}">
                            @csrf

                            <!-- Campaign Name -->
                            <div class="mb-6">
                                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Campaign Name
                                    *</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
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
                                    placeholder="Describe the purpose and goals of this campaign...">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="mb-6">
                                <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status *</label>
                                <select name="status" id="status" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition @error('status') border-red-500 @enderror">
                                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>Paused</option>
                                    <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed
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
                                    <span>Create Campaign</span>
                                </button>
                                <a href="{{ route('campaigns.index') }}"
                                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-6 rounded-lg transition text-center">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Help Text -->
                    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h3 class="text-sm font-semibold text-blue-800">What's Next?</h3>
                                <p class="text-sm text-blue-700 mt-1">After creating your campaign, you can add leads
                                    manually or import them via CSV file.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
@endsection