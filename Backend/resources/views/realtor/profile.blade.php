@extends('layouts.app')

@section('title', 'My Profile - Rumu App')

@section('header-title', 'My Profile')
@section('header-subtitle', 'Manage your account settings and preferences')

@section('content')
    <div class="min-h-screen bg-gray-50">
        @include('realtor.partials.sidebar')

        <!-- Main Content -->
        <main class="lg:ml-64 min-h-screen">
            @include('realtor.partials.header')

            <!-- Content Area -->
            <div class="p-4 sm:p-6 lg:p-8">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Profile Info -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Profile Information Card -->
                        <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                            <form method="POST" action="{{ route('realtor.profile.update') }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <!-- Avatar Section -->
                                <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6 mb-8">
                                    <div class="relative">
                                        @if($user->avatar_url)
                                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" 
                                                class="w-24 h-24 sm:w-28 sm:h-28 rounded-full object-cover border-4 border-primary/20">
                                        @else
                                            <div class="w-24 h-24 sm:w-28 sm:h-28 bg-primary rounded-full flex items-center justify-center text-white font-bold text-4xl border-4 border-primary/20">
                                                {{ $user->avatar_initials }}
                                            </div>
                                        @endif
                                        <label for="avatar" 
                                            class="absolute bottom-0 right-0 w-10 h-10 bg-primary hover:bg-primary/90 text-white rounded-full flex items-center justify-center cursor-pointer shadow-lg transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </label>
                                        <input type="file" name="avatar" id="avatar" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                                    </div>
                                    <div class="text-center sm:text-left">
                                        <h2 class="text-xl font-bold text-gray-800">{{ $user->name }}</h2>
                                        <p class="text-gray-500">{{ $user->email }}</p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary mt-2">
                                            {{ $user->plan?->name ?? 'No Plan' }} Plan
                                        </span>
                                        <p class="text-xs text-gray-400 mt-2">Click the camera icon to change photo</p>
                                    </div>
                                </div>

                                <!-- Avatar Preview (shown when selecting new image) -->
                                <div id="avatar-preview" class="hidden mb-6 p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-4">
                                        <img id="preview-img" src="" alt="Preview" class="w-16 h-16 rounded-full object-cover">
                                        <div>
                                            <p class="text-sm font-medium text-gray-700">New avatar selected</p>
                                            <p class="text-xs text-gray-500">Save changes to update your profile photo</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Name -->
                                    <div>
                                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Full Name *
                                        </label>
                                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Email Address *
                                        </label>
                                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                                    </div>

                                    <!-- Phone -->
                                    <div>
                                        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Phone Number
                                        </label>
                                        <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition"
                                            placeholder="+1 234 567 8900">
                                    </div>

                                    <!-- Company -->
                                    <div>
                                        <label for="company" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Company Name
                                        </label>
                                        <input type="text" name="company" id="company" value="{{ old('company', $user->company) }}"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition"
                                            placeholder="Your company or freelance brand">
                                    </div>
                                </div>

                                <!-- Bio -->
                                <div class="mt-6">
                                    <label for="bio" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Bio
                                    </label>
                                    <textarea name="bio" id="bio" rows="4"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition"
                                        placeholder="Tell us a bit about yourself and your work...">{{ old('bio', $user->bio) }}</textarea>
                                    <p class="text-xs text-gray-500 mt-1">Maximum 1000 characters</p>
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <button type="submit"
                                        class="bg-primary hover:bg-primary/90 text-white font-semibold py-2 px-6 rounded-lg transition flex items-center space-x-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span>Save Changes</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Change Password Card -->
                        <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Change Password
                            </h3>

                            <form method="POST" action="{{ route('realtor.profile.password') }}">
                                @csrf
                                @method('PUT')

                                <div class="space-y-4">
                                    <div>
                                        <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Current Password *
                                        </label>
                                        <input type="password" name="current_password" id="current_password" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                                    </div>

                                    <div>
                                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                            New Password *
                                        </label>
                                        <input type="password" name="password" id="password" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition"
                                            placeholder="Minimum 8 characters">
                                    </div>

                                    <div>
                                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Confirm New Password *
                                        </label>
                                        <input type="password" name="password_confirmation" id="password_confirmation" required
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <button type="submit"
                                        class="bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2 px-6 rounded-lg transition">
                                        Update Password
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Connected Accounts -->
                        <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 8v6m3-3h-6"></path>
                                </svg>
                                Connected Accounts
                            </h3>

                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6" viewBox="0 0 48 48" aria-hidden="true">
                                        <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.654 32.659 29.195 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.047 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917Z"/>
                                        <path fill="#FF3D00" d="M6.306 14.691 12.863 19.5C14.635 15.108 18.959 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.047 6.053 29.268 4 24 4c-7.682 0-14.346 4.326-17.694 10.691Z"/>
                                        <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.197l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.174 0-9.618-3.317-11.289-7.946l-6.51 5.015C9.514 39.556 16.227 44 24 44Z"/>
                                        <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.03 12.03 0 0 1-4.084 5.565l.003-.002 6.19 5.238C36.98 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917Z"/>
                                    </svg>
                                    <div>
                                        <p class="font-semibold text-gray-800">Google</p>
                                        @if($user->google_id)
                                            <p class="text-sm text-green-700">Connected</p>
                                        @else
                                            <p class="text-sm text-gray-500">Not connected</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex gap-3">
                                    @if($user->google_id)
                                        <form method="POST" action="{{ route('connect.google.unlink') }}">
                                            @csrf
                                            <button type="submit"
                                                class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                                                Unlink
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('connect.google.redirect') }}"
                                            class="bg-gray-900 hover:bg-gray-800 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                                            Link Google
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-md bg-[#1877F2] flex items-center justify-center text-white font-bold text-sm">
                                        f
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">Facebook</p>
                                        @if($user->facebook_id)
                                            <p class="text-sm text-green-700">Connected</p>
                                        @else
                                            <p class="text-sm text-gray-500">Not connected</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex gap-3">
                                    @if($user->facebook_id)
                                        <a href="{{ route('connect.facebook.redirect') }}"
                                            class="bg-gray-900 hover:bg-gray-800 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                                            Sync Pages
                                        </a>
                                        <form method="POST" action="{{ route('connect.facebook.unlink') }}">
                                            @csrf
                                            <button type="submit"
                                                class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                                                Unlink
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('connect.facebook.redirect') }}"
                                            class="bg-gray-900 hover:bg-gray-800 text-white font-semibold py-2 px-4 rounded-lg transition text-sm">
                                            Link Facebook
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <p class="text-xs text-gray-500 mt-3">
                                If you haven't uploaded a profile photo, we'll automatically use your Google/Facebook photo when connected.
                            </p>
                        </div>
                    </div>

                    <!-- Right Column: Stats & Info -->
                    <div class="space-y-6">
                        <!-- Stats Card -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Your Stats</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <span class="text-gray-600">Total Campaigns</span>
                                    </div>
                                    <span class="text-xl font-bold text-gray-800">{{ $stats['total_campaigns'] }}</span>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                        </div>
                                        <span class="text-gray-600">Total Leads</span>
                                    </div>
                                    <span class="text-xl font-bold text-gray-800">{{ $stats['total_leads'] }}</span>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                        </div>
                                        <span class="text-gray-600">Active Campaigns</span>
                                    </div>
                                    <span class="text-xl font-bold text-gray-800">{{ $stats['active_campaigns'] }}</span>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <span class="text-gray-600">Won Leads</span>
                                    </div>
                                    <span class="text-xl font-bold text-gray-800">{{ $stats['won_leads'] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Account Info Card -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Account Info</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Member Since</span>
                                    <span class="font-medium text-gray-800">{{ $user->created_at->format('M d, Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Role</span>
                                    <span class="font-medium text-gray-800 capitalize">{{ $user->role }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Plan</span>
                                    <span class="font-medium text-primary">{{ $user->plan?->name ?? 'No Plan' }}</span>
                                </div>
                                @if($user->plan && $user->plan->max_campaigns)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Campaigns Limit</span>
                                    <span class="font-medium text-gray-800">{{ $stats['total_campaigns'] }} / {{ $user->plan->max_campaigns }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Quick Links -->
                        <div class="bg-gradient-to-r from-primary to-primary/80 rounded-xl shadow-lg p-6 text-white">
                            <h3 class="text-lg font-bold mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <a href="{{ route('campaigns.create') }}" class="flex items-center p-3 bg-white/10 rounded-lg hover:bg-white/20 transition">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span>Create New Campaign</span>
                                </a>
                                <a href="{{ route('realtor.plans') }}" class="flex items-center p-3 bg-white/10 rounded-lg hover:bg-white/20 transition">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                    </svg>
                                    <span>View Plans</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('avatar-preview').classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
@endsection
