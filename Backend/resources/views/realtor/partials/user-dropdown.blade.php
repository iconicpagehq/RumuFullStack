@if(Auth::user()->role !== 'admin')
<!-- Realtor User Profile Dropdown -->
<div class="relative shrink-0" id="user-dropdown">
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
