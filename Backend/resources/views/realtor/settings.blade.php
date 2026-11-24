@extends('layouts.app')

@section('title', 'Settings - Rumu App')

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

                <div class="max-w-4xl mx-auto space-y-6">
                    
                    <!-- Notifications Settings -->
                    <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Notifications</h2>
                                <p class="text-gray-500 text-sm">Manage how you receive alerts and updates</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('realtor.settings.notifications') }}">
                            @csrf
                            @method('PUT')

                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Email Notifications</h4>
                                        <p class="text-sm text-gray-500">Receive updates about your campaigns and leads</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="email_notifications" value="1" class="sr-only peer" {{ $user->email_notifications ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Marketing Emails</h4>
                                        <p class="text-sm text-gray-500">Receive tips, offers, and product updates</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="marketing_emails" value="1" class="sr-only peer" {{ $user->marketing_emails ? 'checked' : '' }}>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="bg-primary hover:bg-primary/90 text-white font-semibold py-2 px-6 rounded-lg transition">
                                    Save Notification Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Privacy Settings -->
                    <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Privacy</h2>
                                <p class="text-gray-500 text-sm">Control your profile visibility and data</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('realtor.settings.privacy') }}">
                            @csrf
                            @method('PUT')

                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="font-semibold text-gray-800">Public Profile</h4>
                                    <p class="text-sm text-gray-500">Allow others to see your profile and achievements</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="public_profile" value="1" class="sr-only peer" {{ $user->public_profile ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="bg-primary hover:bg-primary/90 text-white font-semibold py-2 px-6 rounded-lg transition">
                                    Save Privacy Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Appearance Settings -->
                    <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Appearance & Language</h2>
                                <p class="text-gray-500 text-sm">Customize your interface preferences</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('realtor.settings.appearance') }}">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Theme -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Theme</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 {{ $user->theme === 'light' ? 'border-primary bg-primary/5' : 'border-gray-200' }}">
                                            <input type="radio" name="theme" value="light" class="w-4 h-4 text-primary" {{ $user->theme === 'light' ? 'checked' : '' }}>
                                            <span class="ml-3 flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                                </svg>
                                                Light
                                            </span>
                                        </label>
                                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 {{ $user->theme === 'dark' ? 'border-primary bg-primary/5' : 'border-gray-200' }}">
                                            <input type="radio" name="theme" value="dark" class="w-4 h-4 text-primary" {{ $user->theme === 'dark' ? 'checked' : '' }}>
                                            <span class="ml-3 flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                                </svg>
                                                Dark
                                            </span>
                                        </label>
                                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 {{ $user->theme === 'auto' ? 'border-primary bg-primary/5' : 'border-gray-200' }}">
                                            <input type="radio" name="theme" value="auto" class="w-4 h-4 text-primary" {{ $user->theme === 'auto' ? 'checked' : '' }}>
                                            <span class="ml-3 flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                                Auto (System)
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Language & Timezone -->
                                <div class="space-y-4">
                                    <div>
                                        <label for="language" class="block text-sm font-semibold text-gray-700 mb-2">Language</label>
                                        <select name="language" id="language" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            @foreach($languages as $code => $name)
                                                <option value="{{ $code }}" {{ $user->language === $code ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="timezone" class="block text-sm font-semibold text-gray-700 mb-2">Timezone</label>
                                        <select name="timezone" id="timezone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            @foreach($timezones as $value => $label)
                                                <option value="{{ $value }}" {{ $user->timezone === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="bg-primary hover:bg-primary/90 text-white font-semibold py-2 px-6 rounded-lg transition">
                                    Save Appearance Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Delete Account -->
                    <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8 border-2 border-red-100">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Delete Account</h2>
                                <p class="text-gray-500 text-sm">Permanently delete your account and all data</p>
                            </div>
                        </div>

                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <h4 class="font-semibold text-red-800 mb-2">⚠️ Warning</h4>
                            <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                                <li>This action cannot be undone</li>
                                <li>All your campaigns and leads will be deleted</li>
                                <li>Your subscription will be cancelled</li>
                            </ul>
                        </div>

                        <form method="POST" action="{{ route('realtor.settings.account.delete') }}" 
                            onsubmit="return confirm('Are you absolutely sure? This action cannot be undone!');">
                            @csrf
                            @method('DELETE')

                            <div class="space-y-4">
                                <div>
                                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Current Password</label>
                                    <input type="password" name="password" id="password" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                </div>

                                <div>
                                    <label for="confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Type "DELETE" to confirm</label>
                                    <input type="text" name="confirmation" id="confirmation" required placeholder="DELETE"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent uppercase">
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-6 rounded-lg transition flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete My Account
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </main>
    </div>
    <script>
        // Auto-submit theme change for instant preview
        document.querySelectorAll('input[name="theme"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Apply theme immediately for preview
                const html = document.documentElement;
                if (this.value === 'dark') {
                    html.classList.add('dark');
                } else if (this.value === 'light') {
                    html.classList.remove('dark');
                } else if (this.value === 'auto') {
                    // Check system preference
                    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        html.classList.add('dark');
                    } else {
                        html.classList.remove('dark');
                    }
                }
            });
        });
    </script>
@endsection
