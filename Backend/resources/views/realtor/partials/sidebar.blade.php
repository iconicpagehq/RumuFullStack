<!-- Mobile Overlay -->
<div id="sidebar-overlay" class="sidebar-overlay fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden"></div>

<!-- Sidebar -->
<aside id="mobile-sidebar"
    class="sidebar-mobile lg:translate-x-0 w-64 gradient-bg shadow-2xl fixed h-full z-30 overflow-y-auto flex flex-col">
    <!-- Logo/Brand -->
    <div class="p-4 sm:p-6 border-b border-white/20 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <img src="https://iconicpages.com/wp-content/uploads/2025/12/rumu-1.png" alt="RUMU" 
                class="h-10 sm:h-12 w-auto object-contain">
        </div>
        <!-- Close button for mobile -->
        <button onclick="toggleMobileMenu()" class="lg:hidden text-white p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                </path>
            </svg>
        </button>
    </div>

    @if(auth()->check() && auth()->user()->isSubscriptionSuspended())
        <div class="mx-3 sm:mx-4 mt-2 px-2.5 py-2 bg-red-500/25 border border-red-300/40 rounded-md">
            <p class="text-white text-[11px] font-semibold leading-tight">Subscription Suspended</p>
            <a href="{{ route('realtor.plans') }}" class="text-white/90 text-[10px] underline">Renew</a>
        </div>
    @endif

    <!-- Navigation Menu -->
    <nav class="p-3 sm:p-4 flex-1 overflow-y-auto">
        <ul class="space-y-2">
            <li>
                <a href="{{ route('realtor.dashboard') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('realtor.dashboard') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('campaigns.index') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('campaigns.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">My Campaigns</span>
                </a>
            </li>
            <li>
                <a href="{{ route('realtor.customers') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('realtor.customers') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Customers</span>
                </a>
            </li>
            <li>
                <a href="{{ route('inventory.projects.index') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('inventory.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Inventory</span>
                </a>
            </li>
            <li>
                <a href="{{ route('realtor.plans') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('realtor.plans') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Plans</span>
                </a>
            </li>
            <li>
                <a href="{{ route('realtor.profile') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('realtor.profile') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Profile</span>
                </a>
            </li>
            <li>
                <a href="{{ route('realtor.billing.index') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('realtor.billing.index', 'realtor.billing.invoice.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Billing</span>
                </a>
            </li>
            <li>
                <a href="{{ route('realtor.billing.notifications.index') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('realtor.billing.notifications.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Notifications</span>
                    @php
                        $unreadNotificationsCount = \App\Models\BillingNotification::where('user_id', auth()->id())
                            ->whereIn('channel', ['in_app', 'both'])
                            ->whereNull('read_at')
                            ->count();
                    @endphp
                    @if($unreadNotificationsCount > 0)
                        <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $unreadNotificationsCount }}</span>
                    @endif
                </a>
            </li>
            <li>
                <a href="{{ route('realtor.settings') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('realtor.settings*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Settings</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Logout Button -->
    <div class="p-3 sm:p-4 border-t border-white/20 bg-primary/50 flex-shrink-0">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="w-full flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation text-white rounded-lg hover:bg-white/10 transition">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                    </path>
                </svg>
                <span class="font-medium text-sm sm:text-base">Logout</span>
            </button>
        </form>
    </div>
</aside>
