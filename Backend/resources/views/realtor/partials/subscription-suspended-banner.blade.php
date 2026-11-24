@if(auth()->check() && auth()->user()->isSubscriptionSuspended())
    @php
        $suspendedUser = auth()->user();
        $suspendedUser->loadMissing('suspendedPlan');
    @endphp
    <div class="bg-red-50 border border-red-200 rounded-lg px-3 py-2 mb-4 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2 min-w-0 flex-1">
            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3.35L13.74 4.65a2 2 0 00-3.48 0L3.33 15.65A2 2 0 005.07 19z"></path>
            </svg>
            <p class="text-xs sm:text-sm text-red-800 leading-snug min-w-0">
                <span class="font-semibold text-red-900">Subscription suspended</span>
                <span class="text-red-700"> · Read-only</span>
                @if($suspendedUser->suspendedPlan)
                    <span class="hidden sm:inline text-red-600"> · Was {{ $suspendedUser->suspendedPlan->name }}</span>
                @endif
                @if($suspendedUser->subscription_suspend_reason)
                    <span class="block sm:inline text-red-600 sm:before:content-['·'] sm:before:mx-1 italic">{{ $suspendedUser->subscription_suspend_reason }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('realtor.plans') }}"
            class="inline-flex items-center justify-center bg-red-600 hover:bg-red-700 text-white font-medium py-1.5 px-3 rounded-md transition text-xs whitespace-nowrap shrink-0">
            Renew Plan
        </a>
    </div>
@endif
