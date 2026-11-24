@extends('layouts.admin')

@section('title', 'Edit Realtor - ' . $realtor->name)

@section('page-title', 'Edit Realtor')
@section('page-subtitle', 'Update profile and plan settings')

@section('admin-content')
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-6 lg:p-8">
            <form method="POST" action="{{ route('admin.realtors.update', $realtor) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:gap-5 lg:gap-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">Full Name
                            *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $realtor->name) }}"
                            required
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">Email Address
                            *</label>
                        <input type="email" name="email" id="email"
                            value="{{ old('email', $realtor->email) }}" required
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Plan Selection -->
                    <div>
                        <label for="plan_id" class="block text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">Subscription
                            Plan *</label>
                        <select name="plan_id" id="plan_id" required
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition @error('plan_id') border-red-500 @enderror">
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ old('plan_id', $realtor->plan_id) == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }}
                                    ({{ $plan->max_campaigns ? $plan->max_campaigns . ' Campaigns' : 'Unlimited Campaigns' }},
                                    {{ $plan->max_leads_per_campaign ? $plan->max_leads_per_campaign . ' Leads' : 'Unlimited Leads' }})
                                </option>
                            @endforeach
                        </select>
                        @error('plan_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password (Optional) -->
                    <div class="border-t border-gray-100 pt-4 sm:pt-5 lg:pt-6 mt-2">
                        <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4">Change Password</h3>
                        <p class="text-xs sm:text-sm text-gray-500 mb-3 sm:mb-4">Leave blank if you don't want to change the
                            password.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5 lg:gap-6">
                            <div>
                                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">New
                                    Password</label>
                                <input type="password" name="password" id="password"
                                    class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition @error('password') border-red-500 @enderror">
                                @error('password')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation"
                                    class="block text-sm font-semibold text-gray-700 mb-1.5 sm:mb-2">Confirm
                                    Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                    class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 sm:mt-8 flex flex-col sm:flex-row gap-3 sm:gap-4">
                    <button type="submit"
                        class="flex-1 bg-primary hover:bg-primary/90 text-white font-semibold py-3 sm:py-2.5 lg:py-3 px-6 rounded-lg transition text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0">
                        Update Realtor
                    </button>
                    <a href="{{ route('admin.realtors.index') }}"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 sm:py-2.5 lg:py-3 px-6 rounded-lg transition text-center text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center">
                        Cancel
                    </a>
                </div>
            </form>

                        <!-- Delete Account Zone -->
                        <div class="mt-6 sm:mt-8 lg:mt-10 pt-4 sm:pt-5 lg:pt-6 border-t border-red-100">
                            <h3 class="text-base sm:text-lg font-bold text-red-600 mb-1.5 sm:mb-2">Danger Zone</h3>
                            <p class="text-xs sm:text-sm text-gray-600 mb-3 sm:mb-4">Deleting this account will permanently remove all
                                associated campaigns and leads.</p>
                            <form method="POST" action="{{ route('admin.realtors.destroy', $realtor) }}"
                                onsubmit="return confirm('Are you sure you want to delete this Realtor? This action cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="w-full sm:w-auto bg-red-50 text-red-700 hover:bg-red-100 font-semibold py-2.5 sm:py-2 px-6 rounded-lg transition text-sm border border-red-200 touch-manipulation min-h-[44px] sm:min-h-0">
                                    Delete Account
                                </button>
                            </form>
                        </div>
        </div>
                </div>
    </div>
@endsection