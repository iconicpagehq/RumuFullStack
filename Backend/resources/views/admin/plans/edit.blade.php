@extends('layouts.admin')

@section('title', 'Edit Plan - Rumu App')

@section('page-title', 'Edit Plan')
@section('page-subtitle', 'Update plan details and pricing')

@section('header-actions')
    <a href="{{ route('admin.plans.index') }}"
        class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 sm:px-4 py-2.5 sm:py-2 rounded-lg transition flex items-center justify-center space-x-2 text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 whitespace-nowrap">
        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        <span>Back to Plans</span>
    </a>
@endsection

@section('admin-content')
    @if (session('success'))
        <div
            class="bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-2.5 sm:py-3 rounded-lg mb-4 sm:mb-6 animate-fade-in text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-2.5 sm:py-3 rounded-lg mb-4 sm:mb-6 text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-3xl mx-auto">
        <!-- Plan Info Card -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6 text-white mb-4 sm:mb-6" id="plan-header-preview" style="{{ $plan->headerBackgroundStyle() }}">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="bg-white/20 p-3 sm:p-4 rounded-lg">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold">{{ $plan->name }} Plan</h2>
                    <p class="text-white/80 text-xs sm:text-sm mt-0.5">Currently {{ $plan->users()->count() }} active
                        {{ Str::plural('user', $plan->users()->count()) }}</p>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <form action="{{ route('admin.plans.update', $plan) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-4 sm:space-y-5">
                    <!-- Plan Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">
                            Plan Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $plan->name) }}" required
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Price -->
                    <div>
                        <label for="price" class="block text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">
                            Monthly Price (₹)
                        </label>
                        <div class="relative">
                            <span
                                class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-gray-500 text-base">₹</span>
                            <input type="number" name="price" id="price" value="{{ old('price', $plan->price) }}"
                                step="0.01" min="0"
                                class="w-full pl-8 sm:pl-10 pr-3 sm:pr-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('price') border-red-500 @enderror">
                        </div>
                        @error('price')
                            <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Leave empty for free plan</p>
                    </div>

                    <!-- Header Color -->
                    <div>
                        <label for="header_color" class="block text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">
                            Card Header Color
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="header_color_picker"
                                value="{{ old('header_color', $plan->resolvedHeaderColor()) }}"
                                class="h-11 w-14 rounded border border-gray-300 cursor-pointer bg-white p-1">
                            <input type="text" name="header_color" id="header_color"
                                value="{{ old('header_color', $plan->resolvedHeaderColor()) }}"
                                pattern="^#[0-9A-Fa-f]{6}$"
                                class="flex-1 px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent font-mono"
                                placeholder="#9333ea">
                        </div>
                        @error('header_color')
                            <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Shown on the Realtor pricing page card header.</p>
                    </div>

                    <!-- Max Campaigns -->
                    <div>
                        <label for="max_campaigns" class="block text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">
                            Maximum Campaigns
                        </label>
                        <input type="number" name="max_campaigns" id="max_campaigns"
                            value="{{ old('max_campaigns', $plan->max_campaigns) }}" min="0"
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('max_campaigns') border-red-500 @enderror">
                        @error('max_campaigns')
                            <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Leave empty for unlimited campaigns</p>
                    </div>

                    <!-- Max Leads per Campaign -->
                    <div>
                        <label for="max_leads_per_campaign"
                            class="block text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">
                            Maximum Leads per Campaign
                        </label>
                        <input type="number" name="max_leads_per_campaign" id="max_leads_per_campaign"
                            value="{{ old('max_leads_per_campaign', $plan->max_leads_per_campaign) }}" min="0"
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('max_leads_per_campaign') border-red-500 @enderror">
                        @error('max_leads_per_campaign')
                            <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Leave empty for unlimited leads</p>
                    </div>

                    <!-- Active status -->
                    <div class="flex items-start gap-3 p-3 sm:p-4 bg-gray-50 rounded-lg border border-gray-200">
                        @unless($plan->is_default)
                            <input type="hidden" name="is_active" value="0">
                        @endunless
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                            {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                            class="h-4 w-4 mt-1 text-primary focus:ring-primary border-gray-300 rounded"
                            @if($plan->is_default) disabled @endif>
                        <div class="flex-1">
                            <label for="is_active" class="block text-sm font-semibold text-gray-800">Active plan</label>
                            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                                @if($plan->is_default)
                                    Default plan must stay active. Use another default plan before deactivating.
                                @else
                                    Uncheck to hide this plan from Realtors and new registrations.
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-2 sm:pt-4">
                        <button type="submit"
                            class="flex-1 bg-primary hover:bg-primary/90 text-white font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition flex items-center justify-center space-x-2 text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            <span>Update Plan</span>
                        </button>
                        <a href="{{ route('admin.plans.index') }}"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition text-center text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Current Plan Stats -->
        <div class="mt-4 sm:mt-6 bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4">Current Plan Statistics</h3>
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                    <p class="text-xs sm:text-sm text-gray-600">Active Users</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ $plan->users()->count() }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
                    <p class="text-xs sm:text-sm text-gray-600">Total Revenue</p>
                    <p class="text-xl sm:text-2xl font-bold text-green-600 mt-1">
                        ₹{{ number_format(($plan->price ?? 0) * $plan->users()->count()) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    const picker = document.getElementById('header_color_picker');
    const hexInput = document.getElementById('header_color');
    const preview = document.getElementById('plan-header-preview');
    if (!picker || !hexInput) return;

    function darken(hex, amount) {
        const value = hex.replace('#', '');
        if (value.length !== 6) return '#4b5563';
        const r = Math.max(0, Math.min(255, Math.round(parseInt(value.slice(0, 2), 16) * (1 - amount))));
        const g = Math.max(0, Math.min(255, Math.round(parseInt(value.slice(2, 4), 16) * (1 - amount))));
        const b = Math.max(0, Math.min(255, Math.round(parseInt(value.slice(4, 6), 16) * (1 - amount))));
        return '#' + [r, g, b].map(function (n) { return n.toString(16).padStart(2, '0'); }).join('');
    }

    function applyPreview(color) {
        if (!preview || !/^#[0-9A-Fa-f]{6}$/.test(color)) return;
        preview.style.background = 'linear-gradient(to right, ' + color + ', ' + darken(color, 0.15) + ')';
    }

    picker.addEventListener('input', function () {
        hexInput.value = picker.value;
        applyPreview(picker.value);
    });

    hexInput.addEventListener('input', function () {
        if (/^#[0-9A-Fa-f]{6}$/.test(hexInput.value)) {
            picker.value = hexInput.value;
            applyPreview(hexInput.value);
        }
    });
})();
</script>
@endsection