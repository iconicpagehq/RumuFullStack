<header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-10">
    <div class="px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center space-x-3 min-w-0">
                <button onclick="toggleMobileMenu()" class="lg:hidden text-gray-600 hover:text-primary p-2 -ml-2 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="min-w-0">
                    @if(!empty($backUrl))
                        <a href="{{ $backUrl }}" class="text-sm text-primary">{{ $backLabel ?? '← Back' }}</a>
                    @endif
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 truncate">{{ $title }}</h1>
                    @if(!empty($subtitle))
                        <p class="text-gray-500 text-sm hidden sm:block">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                @if(!empty($actionUrl) && Auth::user()->role === 'realtor')
                    <a href="{{ $actionUrl }}" class="bg-primary hover:bg-primary/90 text-white font-semibold py-2 px-4 rounded-lg text-sm whitespace-nowrap">{{ $actionLabel ?? 'New' }}</a>
                @endif
                @include('realtor.partials.user-dropdown')
            </div>
        </div>
    </div>
</header>
