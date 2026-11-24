@extends('layouts.app')

@section('title', $campaign->name . ' - Rumu App')

@section('content')
    <div class="min-h-screen bg-gray-50">
        @if(Auth::user()->role === 'admin')
            @include('admin.partials.sidebar')
        @else
            @include('realtor.partials.sidebar')
        @endif

        <main class="lg:ml-64 min-h-screen">
            <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-10">
                <div class="px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center space-x-3 sm:space-x-4">
                            <button onclick="toggleMobileMenu()"
                                class="lg:hidden text-gray-600 hover:text-primary p-2 -ml-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <div>
                                <div class="flex items-center space-x-3">
                                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800">
                                        {{ $campaign->name }}</h1>
                                    <span
                                        class="px-2 sm:px-3 py-1 text-xs font-semibold rounded-full {{ $campaign->status === 'active' ? 'bg-green-100 text-green-800' : ($campaign->status === 'paused' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                                </div>
                                <p class="text-gray-500 mt-1 text-sm">{{ $campaign->leads->count() }} leads • Created
                                    {{ $campaign->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('campaigns.edit', $campaign) }}"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg transition text-sm">
                                Edit Campaign
                            </a>
                            <a href="{{ route('campaigns.meta-integration', $campaign) }}"
                                class="bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-2 px-4 rounded-lg transition text-sm flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                                </svg>
                                Manage Meta Integration
                            </a>
                            <a href="{{ route('campaigns.index') }}"
                                class="bg-primary hover:bg-primary/90 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                                Back to Campaigns
                            </a>
                            
                            @if(Auth::user()->role !== 'admin')
                            <!-- Realtor User Profile Dropdown -->
                            <div class="relative ml-2" id="user-dropdown">
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

            <div class="p-4 sm:p-6 lg:p-8">
                @include('realtor.partials.subscription-suspended-banner')

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-lg mb-6">
                        {{ session('warning') }}
                        @if(session('import_errors'))
                            <ul class="list-disc list-inside text-sm mt-2">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-blue-500">
                        <p class="text-gray-500 text-xs font-medium">Total Leads</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $campaign->leads->count() }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-green-500">
                        <p class="text-gray-500 text-xs font-medium">Won</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">
                            {{ $campaign->leads->where('status', 'won')->count() }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-yellow-500">
                        <p class="text-gray-500 text-xs font-medium">In Progress</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">
                            {{ $campaign->leads->whereIn('status', ['contacted', 'qualified', 'proposal', 'negotiation'])->count() }}
                        </p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-red-500">
                        <p class="text-gray-500 text-xs font-medium">Lost</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">
                            {{ $campaign->leads->where('status', 'lost')->count() }}</p>
                    </div>
                </div>

                <!-- Leads Section -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div
                        class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-primary to-primary/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <h2 class="text-lg sm:text-xl font-bold text-white">Leads</h2>

                    </div>

                    @if($campaign->leads->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name</th>
                                        <th
                                            class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                            Email</th>
                                        <th
                                            class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                                            Phone</th>
                                        <th
                                            class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status</th>
                                        <th
                                            class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($campaign->leads as $lead)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $lead->name }}</div>
                                                @if($lead->company)
                                                    <div class="text-xs text-gray-500">{{ $lead->company }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap hidden md:table-cell">
                                                <div class="text-sm text-gray-500">{{ $lead->email ?? '-' }}</div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap hidden sm:table-cell">
                                                <div class="text-sm text-gray-500">{{ $lead->phone ?? '-' }}</div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                                <span
                                                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $lead->status_color }}-100 text-{{ $lead->status_color }}-800">
                                                    {{ ucfirst($lead->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-sm">
                                                <a href="{{ route('leads.show', $lead) }}" class="text-primary hover:text-primary/80 font-semibold">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-8 sm:p-12 text-center">
                            <svg class="w-16 h-16 sm:w-20 sm:h-20 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                </path>
                            </svg>
                            <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-2">No Leads Yet</h3>
                            <p class="text-gray-600 mb-6">Add leads manually, import CSV, or import from Meta Ads</p>
                            <div class="flex flex-col sm:flex-row flex-wrap gap-3 justify-center">
                                <a href="{{ route('leads.create', $campaign) }}"
                                    class="bg-primary hover:bg-primary/90 text-white font-semibold py-2 px-6 rounded-lg transition text-center">
                                    Add Lead Manually
                                </a>
                                <a href="{{ route('leads.import.meta.page', $campaign) }}"
                                    class="bg-[#1877F2] hover:bg-[#166FE5] text-white font-semibold py-2 px-6 rounded-lg transition text-center">
                                    Import Meta Ads
                                </a>
                                <a href="{{ route('leads.create', $campaign) }}?tab=csv"
                                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition text-center">
                                    Import CSV
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </div>
@endsection