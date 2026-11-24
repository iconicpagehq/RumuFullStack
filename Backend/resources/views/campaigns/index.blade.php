@extends('layouts.app')

@section('title', 'Campaigns - Rumu App')

@section('content')
    <div class="min-h-screen bg-gray-50">
        @if(Auth::user()->role === 'admin')
            @include('admin.partials.sidebar')
        @else
            @include('realtor.partials.sidebar')
        @endif

        <!-- Main Content -->
        <main class="lg:ml-64 min-h-screen">
            <!-- Header -->
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
                                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800">My Campaigns</h1>
                                <p class="text-gray-500 mt-1 text-sm sm:text-base hidden sm:block">Manage your lead
                                    generation campaigns</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('campaigns.create') }}"
                                class="bg-primary hover:bg-primary/90 text-white font-semibold py-2 px-4 sm:px-6 rounded-lg transition flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span class="hidden sm:inline">New Campaign</span>
                                <span class="sm:hidden">New</span>
                            </a>
                            
                            @if(Auth::user()->role !== 'admin')
                            <!-- Realtor User Profile Dropdown -->
                            <div class="relative" id="user-dropdown">
                                <button onclick="toggleUserDropdown()" class="flex items-center space-x-2 focus:outline-none">
                                    @if(Auth::user()->avatar_url)
                                        <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" 
                                            class="w-10 h-10 rounded-full object-cover border-2 border-primary/20">
                                    @else
                                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-bold text-lg border-2 border-primary/20">
                                            {{ Auth::user()->avatar_initials }}
                                        </div>
                                    @endif
                                    <svg class="w-4 h-4 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <!-- Dropdown Menu -->
                                <div id="user-dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 border border-gray-100">
                                    <div class="px-4 py-2 border-b border-gray-100">
                                        <p class="text-sm font-semibold text-gray-800">{{ Auth::user()->name }}</p>
                                        <p class="text-xs text-gray-500">{{ Auth::user()->plan?->name ?? 'Free' }} Plan</p>
                                    </div>
                                    <a href="{{ route('realtor.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            My Profile
                                        </span>
                                    </a>
                                    <a href="{{ route('realtor.plans') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                            </svg>
                                            Plans
                                        </span>
                                    </a>
                                    <div class="border-t border-gray-100 my-1"></div>
                                    <form method="POST" action="{{ route('logout') }}" class="block">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                                </svg>
                                                Logout
                                            </span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <script>
                                function toggleUserDropdown() {
                                    const menu = document.getElementById('user-dropdown-menu');
                                    menu.classList.toggle('hidden');
                                }
                                document.addEventListener('click', function(event) {
                                    const dropdown = document.getElementById('user-dropdown');
                                    const menu = document.getElementById('user-dropdown-menu');
                                    if (dropdown && !dropdown.contains(event.target)) {
                                        menu.classList.add('hidden');
                                    }
                                });
                            </script>
                            @endif
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="p-4 sm:p-6 lg:p-8">
                @include('realtor.partials.subscription-suspended-banner')

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Campaigns Grid -->
                @if($campaigns->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                        @foreach($campaigns as $campaign)
                            <div
                                class="bg-white rounded-xl shadow-lg p-4 sm:p-6 hover-lift border-l-4 {{ $campaign->status === 'active' ? 'border-green-500' : ($campaign->status === 'paused' ? 'border-yellow-500' : 'border-gray-500') }}">
                                <div class="flex items-start justify-between mb-3">
                                    <h3 class="text-lg font-bold text-gray-800 flex-1">{{ $campaign->name }}</h3>
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full {{ $campaign->status === 'active' ? 'bg-green-100 text-green-800' : ($campaign->status === 'paused' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                                </div>

                                @if($campaign->description)
                                    <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ $campaign->description }}</p>
                                @endif

                                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                    <span>{{ $campaign->leads->count() }} leads</span>
                                    <span>{{ $campaign->created_at->format('M d, Y') }}</span>
                                </div>

                                <div class="flex space-x-2">
                                    <a href="{{ route('campaigns.show', $campaign) }}"
                                        class="flex-1 bg-primary hover:bg-primary/90 text-white text-center py-2 px-3 rounded-lg transition text-sm font-semibold">
                                        View
                                    </a>
                                    <a href="{{ route('campaigns.edit', $campaign) }}"
                                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-3 rounded-lg transition text-sm font-semibold">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-xl shadow-lg p-8 sm:p-12 text-center">
                        <svg class="w-16 h-16 sm:w-24 sm:h-24 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg>
                        <h3 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">No Campaigns Yet</h3>
                        <p class="text-gray-600 mb-6">Create your first campaign to start managing leads</p>
                        <a href="{{ route('campaigns.create') }}"
                            class="inline-flex items-center bg-primary hover:bg-primary/90 text-white font-semibold py-3 px-6 rounded-lg transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Campaign
                        </a>
                    </div>
                @endif
            </div>
        </main>
    </div>
@endsection