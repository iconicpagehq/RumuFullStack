{{-- Admin Sidebar Component --}}
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

    <!-- Navigation Menu -->
    <nav class="p-3 sm:p-4 flex-1 overflow-y-auto" id="admin-sidebar-nav">
        <ul class="space-y-2" id="admin-sidebar-menu">
            <li>
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.dashboard') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Campaigns</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.realtors.index') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.realtors.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Realtors</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.leads.index') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.leads.*') || request()->routeIs('leads.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                        </path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">All Leads</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.plans.index') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.plans.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                        </path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Plans</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.upgrade_requests.index') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.upgrade_requests.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Upgrade Requests</span>
                    @php
                        $pendingUpgradeCount = \App\Models\PlanUpgradeRequest::where('status', 'pending')->count();
                    @endphp
                    @if($pendingUpgradeCount > 0)
                        <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingUpgradeCount }}</span>
                    @endif
                </a>
            </li>
            <li>
                <div class="px-3 sm:px-4 py-2 mt-4 flex items-center justify-between">
                    <span class="text-xs font-semibold text-white/50 uppercase tracking-wider">Analytics & BI</span>
                </div>
            </li>
            <li>
                <a href="{{ route('admin.analytics.dashboard') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.analytics.dashboard') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.analytics.revenue') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.analytics.revenue') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Revenue</span>
                </a>
            </li>


                <a href="{{ route('admin.analytics.payments') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.analytics.payments') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Payments</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.analytics.reports') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.analytics.reports') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Reports</span>
                </a>
            </li>
            <li>
                <div class="px-3 sm:px-4 py-2 mt-4">
                    <span class="text-xs font-semibold text-white/50 uppercase tracking-wider">Billing</span>
                </div>
            </li>


            <li>
                <a href="{{ route('admin.billing.subscriptions') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.billing.subscriptions') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Subscriptions</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.billing.payments') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.billing.payments') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Payments</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.billing.invoices') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.billing.invoices') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Invoices</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.notifications.index') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.notifications.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Notifications</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.billing.coupons.index') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.billing.coupons.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Coupons</span>
                </a>
            </li>

            <li>
                <a href="{{ route('admin.settings.index') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.settings.index') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Settings</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.settings.billing') }}"
                    class="flex items-center space-x-3 px-3 sm:px-4 py-3 sm:py-2.5 min-h-[44px] touch-manipulation {{ request()->routeIs('admin.settings.billing') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }} rounded-lg transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    <span class="font-medium text-sm sm:text-base">Billing Settings</span>
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