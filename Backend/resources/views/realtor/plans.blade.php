@extends('layouts.app')

@section('title', 'Plans & Pricing - Rumu App')

@section('header-title', 'Plans & Pricing')
@section('header-subtitle', 'Choose the perfect plan for your needs')

@section('content')
    <div class="min-h-screen bg-gray-50">
        @include('realtor.partials.sidebar')

        <!-- Main Content -->
        <main class="lg:ml-64 min-h-screen">
            @include('realtor.partials.header')

            <!-- Content Area -->
            <div class="p-3 sm:p-4 lg:p-6 xl:p-8">
                <div class="animate-fade-in">
                    @if(session('warning'))
                        <div class="bg-amber-50 border border-amber-200 rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-6 mb-4 sm:mb-6">
                            <p class="text-amber-800 text-sm sm:text-base">{{ session('warning') }}</p>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-50 border border-red-200 rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-6 mb-4 sm:mb-6">
                            <p class="text-red-800 text-sm sm:text-base">{{ session('error') }}</p>
                        </div>
                    @endif

                    <!-- Pending Request Alert -->
                    @if($pendingRequest)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-6 mb-4 sm:mb-6">
                            <div class="flex items-start space-x-3">
                                <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h3 class="text-base sm:text-lg font-bold text-yellow-800">Upgrade Request Pending</h3>
                                    <p class="text-yellow-700 text-sm mt-1">You have a pending upgrade request to <strong>{{ $pendingRequest->requestedPlan->name }}</strong> plan. Please wait for admin approval.</p>
                                    <p class="text-yellow-600 text-xs mt-2">Requested on: {{ $pendingRequest->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Current Plan Banner -->
                    @if($currentPlan)
                        <div class="bg-gradient-to-r from-primary to-primary/80 rounded-lg sm:rounded-xl shadow-lg p-3 sm:p-4 lg:p-6 mb-4 sm:mb-6 lg:mb-8 text-white">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                                <div>
                                    <h3 class="text-base sm:text-lg lg:text-xl font-bold mb-1">{{ $currentPlan->name }} Plan</h3>
                                    <p class="text-secondary text-xs sm:text-sm">
                                        @if($currentPlan->max_campaigns)
                                            {{ $userCampaignsCount }} / {{ $currentPlan->max_campaigns }} campaigns used
                                        @else
                                            {{ $userCampaignsCount }} campaigns created (Unlimited)
                                        @endif
                                    </p>

                                </div>
                                @if($currentPlan->key !== 'pro' && !$pendingRequest)
                                    <a href="{{ route('checkout.show', ['plan' => 'pro']) }}" 
                                        class="bg-white text-primary hover:bg-gray-100 font-semibold py-2.5 sm:py-2 px-4 sm:px-6 rounded-lg transition text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center whitespace-nowrap">
                                        Upgrade to Pro
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Plans Grid -->
                    <div id="plans" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 lg:gap-6">
                        @foreach($plans as $plan)
                            <div class="bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden hover-lift {{ $currentPlan && $currentPlan->id === $plan->id ? 'ring-2 ring-primary' : '' }}">
                                <!-- Plan Header -->
                                <div class="p-4 sm:p-5 lg:p-6 text-white text-center" style="{{ $plan->headerBackgroundStyle() }}">
                                    <h3 class="text-xl sm:text-2xl font-bold mb-1">{{ $plan->name }}</h3>
                                    @if($plan->price > 0)
                                        <div class="mt-2">
                                            <span class="text-3xl sm:text-4xl font-bold">₹{{ number_format($plan->price, 0) }}</span>
                                            <span class="text-sm sm:text-base opacity-90">/month</span>
                                        </div>
                                    @else
                                        <p class="text-base sm:text-lg mt-2 opacity-90">Free</p>
                                    @endif
                                </div>

                                <!-- Plan Features -->
                                <div class="p-4 sm:p-5 lg:p-6">
                                    <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6">
                                        <li class="flex items-start">
                                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-sm sm:text-base text-gray-700">
                                                @if($plan->max_campaigns)
                                                    {{ $plan->max_campaigns }} Campaigns
                                                @else
                                                    Unlimited Campaigns
                                                @endif
                                            </span>
                                        </li>
                                        <li class="flex items-start">
                                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-sm sm:text-base text-gray-700">
                                                @if($plan->max_leads_per_campaign)
                                                    {{ number_format($plan->max_leads_per_campaign) }} Leads per Campaign
                                                @else
                                                    Unlimited Leads per Campaign
                                                @endif
                                            </span>
                                        </li>
                                        <li class="flex items-start">
                                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-sm sm:text-base text-gray-700">Full Lead Management</span>
                                        </li>
                                        <li class="flex items-start">
                                            <svg class="w-5 h-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-sm sm:text-base text-gray-700">Priority Support</span>
                                        </li>
                                    </ul>

                                    <!-- Expiry Timer with Progress Bar (only for Pro plan) -->
                                    @if($plan->key === 'pro' && $expiryInfo && $currentPlan && $currentPlan->id === $plan->id)
                                        <div class="mb-4" id="pro-timer" data-expires="{{ $expiryInfo['expires_at']->timestamp }}" data-total="{{ $expiryInfo['total_duration'] }}">
                                            <!-- Progress Bar with Time Inside -->
                                            <div class="relative bg-gray-200 rounded-full h-12 overflow-hidden">
                                                <!-- Progress Fill -->
                                                <div class="absolute top-0 left-0 h-full bg-gradient-to-r from-purple-500 to-purple-600 transition-all duration-1000" 
                                                     id="timer-progress-bar" 
                                                     style="width: {{ $expiryInfo['progress_percent'] }}%">
                                                </div>
                                                <!-- Time Display Overlay -->
                                                <div class="absolute inset-0 flex items-center justify-center">
                                                    <span class="text-sm font-bold text-gray-700 drop-shadow-sm" id="timer-text">
                                                        {{ $expiryInfo['days_remaining'] }}d {{ $expiryInfo['hours_remaining'] }}h {{ $expiryInfo['minutes_remaining'] }}m {{ $expiryInfo['seconds_remaining'] }}s
                                                    </span>
                                                </div>
                                            </div>
                                            <!-- Expiry Date Below -->
                                            <p class="text-xs text-gray-500 text-center mt-1">
                                                Expires on {{ $expiryInfo['expires_at']->format('M d, Y') }}
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Action Button -->
                                    @if($currentPlan && $currentPlan->id === $plan->id)
                                        <button disabled
                                            class="w-full bg-gray-800 text-white font-semibold py-3 sm:py-2.5 px-4 rounded-lg cursor-default text-sm sm:text-base">
                                            Current Plan
                                        </button>
                                    @elseif($pendingRequest && (int) $pendingRequest->requested_plan_id === (int) $plan->id)
                                        <button disabled
                                            class="w-full bg-yellow-100 text-yellow-700 font-semibold py-3 sm:py-2.5 px-4 rounded-lg cursor-not-allowed text-sm sm:text-base">
                                            Request Pending
                                        </button>
                                    @elseif($pendingRequest)
                                        <button disabled
                                            class="w-full bg-gray-100 text-gray-500 font-semibold py-3 sm:py-2.5 px-4 rounded-lg cursor-not-allowed text-sm sm:text-base">
                                            Upgrade Pending
                                        </button>
                                    @elseif($plan->isUpgradeFrom($currentPlan))
                                        <a href="{{ route('checkout.show', ['plan' => $plan->key]) }}"
                                            class="w-full inline-block text-center font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 {{ $plan->isPro() ? 'bg-primary hover:bg-primary/90 text-white' : 'bg-secondary hover:bg-secondary/90 text-primary' }}">
                                            Upgrade to {{ $plan->name }}
                                        </a>
                                    @else
                                        <button disabled
                                            class="w-full bg-gray-100 text-gray-400 font-semibold py-3 sm:py-2.5 px-4 rounded-lg cursor-not-allowed text-sm sm:text-base">
                                            Not Available
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Additional Info -->
                    <div class="mt-6 sm:mt-8 bg-secondary/20 border border-secondary rounded-lg sm:rounded-xl p-4 sm:p-5 lg:p-6">
                        <h3 class="text-base sm:text-lg font-bold text-primary mb-2">💡 Need Help Choosing?</h3>
                        <p class="text-gray-700 text-sm sm:text-base mb-3">All plans include full access to our lead generation tools. Upgrade anytime to unlock more campaigns and leads!</p>
                        <p class="text-gray-600 text-xs sm:text-sm">Questions? Contact our support team for personalized recommendations.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <!-- Live Countdown Timer Script for Pro Card -->
    @if($expiryInfo && $currentPlan->key === 'pro')
    <script>
        function updateCountdown() {
            const timerElement = document.getElementById('pro-timer');
            if (!timerElement) return;
            
            const expiresAt = parseInt(timerElement.dataset.expires) * 1000; // Convert to milliseconds
            const totalDuration = parseInt(timerElement.dataset.total) * 1000; // 30 days in ms
            const now = new Date().getTime();
            const distance = expiresAt - now;
            
            if (distance < 0) {
                // Plan expired - refresh page to show Basic plan
                location.reload();
                return;
            }
            
            // Calculate time units
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            // Update text display
            const textEl = document.getElementById('timer-text');
            if (textEl) {
                textEl.textContent = days + 'd ' + hours.toString().padStart(2, '0') + 'h ' + minutes.toString().padStart(2, '0') + 'm ' + seconds.toString().padStart(2, '0') + 's';
            }
            
            // Update progress bar width
            const progressEl = document.getElementById('timer-progress-bar');
            if (progressEl) {
                const remainingPercent = Math.max(0, (distance / totalDuration) * 100);
                progressEl.style.width = remainingPercent + '%';
                
                // Change color based on remaining time
                if (remainingPercent < 10) {
                    progressEl.className = 'absolute top-0 left-0 h-full bg-gradient-to-r from-red-500 to-red-600 transition-all duration-1000';
                } else if (remainingPercent < 30) {
                    progressEl.className = 'absolute top-0 left-0 h-full bg-gradient-to-r from-yellow-500 to-orange-500 transition-all duration-1000';
                }
            }
        }
        
        // Update immediately and then every second
        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>
    @endif
@endsection
