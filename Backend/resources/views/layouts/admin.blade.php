@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-gray-50">
        {{-- Include Admin Sidebar --}}
        @include('admin.partials.sidebar')

        {{-- Main Content --}}
        <main class="lg:ml-64 min-h-screen">
            {{-- Header with Mobile Menu Button --}}
            <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-10">
                <div class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4 lg:py-6">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center space-x-2 sm:space-x-3 lg:space-x-4 min-w-0 flex-1">
                            {{-- Mobile Menu Button --}}
                            <button onclick="toggleMobileMenu()"
                                class="lg:hidden text-gray-600 hover:text-primary p-2 -ml-2 touch-manipulation">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <div class="min-w-0 flex-1">
                                <h1 class="text-base sm:text-lg lg:text-xl xl:text-2xl font-bold text-gray-800 truncate">
                                    @yield('page-title', 'Admin Panel')
                                </h1>
                                <p class="text-gray-500 mt-0.5 text-xs hidden sm:block truncate">
                                    @yield('page-subtitle', 'Welcome back, ' . Auth::user()->name . '!')
                                </p>
                            </div>
                        </div>
                        {{-- Header Actions --}}
                        <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                            {{-- Notification Bell --}}
                            @php
                                $unreadCount = auth()->user()->unreadNotificationsCount();
                                $recentNotifications = auth()->user()->notifications()->latest()->take(5)->get();
                            @endphp
                            <div class="relative" id="notification-dropdown">
                                <button onclick="toggleNotifications()" 
                                    class="relative p-1.5 sm:p-2 text-gray-600 hover:text-primary hover:bg-gray-100 rounded-lg transition">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                                        </path>
                                    </svg>
                                    @if($unreadCount > 0)
                                        <span class="absolute -top-0.5 -right-0.5 sm:-top-1 sm:-right-1 bg-red-500 text-white text-[10px] sm:text-xs font-bold w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center rounded-full animate-pulse">
                                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                        </span>
                                    @endif
                                </button>
                                
                                {{-- Notification Dropdown --}}
                                <div id="notification-menu" class="hidden fixed sm:absolute right-2 sm:right-0 left-2 sm:left-auto top-16 sm:top-auto sm:mt-2 w-auto sm:w-80 bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden max-w-sm">
                                    <div class="px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                                        <h3 class="font-semibold text-gray-800 text-sm sm:text-base">Notifications</h3>
                                        @if($unreadCount > 0)
                                            <form action="{{ route('admin.notifications.mark-all-read') }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs text-primary hover:text-primary/80 font-medium">
                                                    Mark all read
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    <div class="max-h-[60vh] sm:max-h-80 overflow-y-auto">
                                        @forelse($recentNotifications as $notification)
                                            <div class="px-3 sm:px-4 py-2.5 sm:py-3 border-b border-gray-100 hover:bg-gray-50 transition {{ $notification->is_read ? '' : 'bg-blue-50/50' }}">
                                                <div class="flex items-start gap-2 sm:gap-3">
                                                    <div class="flex-shrink-0 w-7 h-7 sm:w-8 sm:h-8 rounded-full {{ $notification->is_read ? 'bg-gray-100' : 'bg-primary/10' }} flex items-center justify-center">
                                                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 {{ $notification->is_read ? 'text-gray-500' : 'text-primary' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                        </svg>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-xs sm:text-sm font-medium text-gray-800">{{ $notification->title }}</p>
                                                        <p class="text-xs text-gray-500 truncate">{{ $notification->message }}</p>
                                                        <p class="text-[10px] sm:text-xs text-gray-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                                                    </div>
                                                    @if(!$notification->is_read)
                                                        <form action="{{ route('admin.notifications.mark-read', $notification) }}" method="POST" class="flex-shrink-0">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="w-2 h-2 bg-primary rounded-full hover:bg-primary/80" title="Mark as read"></button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="px-4 py-6 sm:py-8 text-center text-gray-500">
                                                <svg class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 sm:mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                                </svg>
                                                <p class="text-xs sm:text-sm">No notifications yet</p>
                                            </div>
                                        @endforelse
                                    </div>
                                    <div class="px-3 sm:px-4 py-2 bg-gray-50 border-t border-gray-200 text-center">
                                        <a href="{{ route('admin.notifications.index') }}" class="text-xs sm:text-sm text-primary hover:text-primary/80 font-medium">
                                            View all notifications
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            @yield('header-actions')
                        </div>
                    </div>
                </div>
            </header>

            {{-- Content Area --}}
            <div class="p-3 sm:p-4 lg:p-6 xl:p-8">
                <div class="animate-fade-in">
                    @yield('admin-content')
                </div>
            </div>
        </main>
    </div>
    
    {{-- Mobile Notification Backdrop --}}
    <div id="notification-backdrop" class="hidden fixed inset-0 bg-black/30 z-40 sm:hidden" onclick="closeNotifications()"></div>
    
    <script>
        // Toggle notification dropdown
        function toggleNotifications() {
            const menu = document.getElementById('notification-menu');
            const backdrop = document.getElementById('notification-backdrop');
            const isHidden = menu.classList.contains('hidden');
            
            if (isHidden) {
                menu.classList.remove('hidden');
                backdrop.classList.remove('hidden');
            } else {
                closeNotifications();
            }
        }
        
        // Close notification dropdown
        function closeNotifications() {
            const menu = document.getElementById('notification-menu');
            const backdrop = document.getElementById('notification-backdrop');
            menu.classList.add('hidden');
            backdrop.classList.add('hidden');
        }
        
        // Close notification dropdown when clicking outside (for desktop)
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('notification-dropdown');
            const menu = document.getElementById('notification-menu');
            if (dropdown && !dropdown.contains(event.target) && window.innerWidth >= 640) {
                menu.classList.add('hidden');
            }
        });
    </script>
@endsection