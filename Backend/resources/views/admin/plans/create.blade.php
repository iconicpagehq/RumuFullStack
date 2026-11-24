@extends('layouts.admin')

@section('title', 'Create Plan - Rumu App')

@section('page-title', 'Create Plan')
@section('page-subtitle', 'Add a new subscription plan')

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
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <form action="{{ route('admin.plans.store') }}" method="POST">
                @csrf

                <div class="space-y-4 sm:space-y-5">
                    <!-- Plan Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">
                            Plan Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
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
                            <span class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-gray-500 text-base">₹</span>
                            <input type="number" name="price" id="price" value="{{ old('price') }}" step="0.01" min="0"
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
                                value="{{ old('header_color', '#6b7280') }}"
                                class="h-11 w-14 rounded border border-gray-300 cursor-pointer bg-white p-1">
                            <input type="text" name="header_color" id="header_color"
                                value="{{ old('header_color', '#6b7280') }}"
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
                        <input type="number" name="max_campaigns" id="max_campaigns" value="{{ old('max_campaigns') }}" min="0"
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('max_campaigns') border-red-500 @enderror">
                        @error('max_campaigns')
                            <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Leave empty for unlimited campaigns</p>
                    </div>

                    <!-- Max Leads per Campaign -->
                    <div>
                        <label for="max_leads_per_campaign" class="block text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">
                            Maximum Leads per Campaign
                        </label>
                        <input type="number" name="max_leads_per_campaign" id="max_leads_per_campaign" value="{{ old('max_leads_per_campaign') }}" min="0"
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent @error('max_leads_per_campaign') border-red-500 @enderror">
                        @error('max_leads_per_campaign')
                            <p class="text-red-500 text-xs sm:text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">Leave empty for unlimited leads</p>
                    </div>

                    <!-- Default Plan -->
                    <div class="flex items-start gap-3 p-3 sm:p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <input type="checkbox" name="is_default" id="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                            class="h-4 w-4 mt-1 text-primary focus:ring-primary border-gray-300 rounded">
                        <div class="flex-1">
                            <label for="is_default" class="block text-sm font-semibold text-gray-800">Set as default plan</label>
                            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                                If enabled, this plan becomes the default for new users (and any previous default will be cleared).
                            </p>
                        </div>
                    </div>

                    <!-- Active Plan -->
                    <div class="flex items-start gap-3 p-3 sm:p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                            class="h-4 w-4 mt-1 text-primary focus:ring-primary border-gray-300 rounded">
                        <div class="flex-1">
                            <label for="is_active" class="block text-sm font-semibold text-gray-800">Active plan</label>
                            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                                Inactive plans are hidden from Realtors and cannot be selected for new signups.
                            </p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-2 sm:pt-4">
                        <button type="submit"
                            class="flex-1 bg-primary hover:bg-primary/90 text-white font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition flex items-center justify-center space-x-2 text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Create Plan</span>
                        </button>
                        <a href="{{ route('admin.plans.index') }}"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition text-center text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center">
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    const picker = document.getElementById('header_color_picker');
    const hexInput = document.getElementById('header_color');
    if (!picker || !hexInput) return;

    picker.addEventListener('input', function () {
        hexInput.value = picker.value;
    });

    hexInput.addEventListener('input', function () {
        if (/^#[0-9A-Fa-f]{6}$/.test(hexInput.value)) {
            picker.value = hexInput.value;
        }
    });
})();
</script>
@endsection

