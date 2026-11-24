@extends('layouts.admin')

@section('title', 'Settings - Rumu App')

@section('page-title', 'Settings')
@section('page-subtitle', 'Manage system settings and configurations')

@section('admin-content')
    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-2.5 sm:py-3 rounded-lg mb-4 sm:mb-6 animate-fade-in text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4 lg:gap-6">
        <!-- Appearance Settings -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <div class="flex items-center mb-3 sm:mb-4">
                <div class="bg-purple-500/10 p-2 sm:p-2.5 lg:p-3 rounded-lg mr-3 sm:mr-4">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                    </svg>
                </div>
                <h2 class="text-base sm:text-lg lg:text-xl font-bold text-gray-800">Appearance</h2>
            </div>
            <form action="{{ route('admin.settings.appearance') }}" method="POST">
                @csrf
                <div class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Theme</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 {{ auth()->user()->theme === 'light' ? 'border-primary bg-primary/5' : 'border-gray-200' }}">
                            <input type="radio" name="theme" value="light" class="w-4 h-4 text-primary" {{ auth()->user()->theme === 'light' ? 'checked' : '' }}>
                            <span class="ml-3 flex items-center text-sm">
                                <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                Light
                            </span>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 {{ auth()->user()->theme === 'dark' ? 'border-primary bg-primary/5' : 'border-gray-200' }}">
                            <input type="radio" name="theme" value="dark" class="w-4 h-4 text-primary" {{ auth()->user()->theme === 'dark' ? 'checked' : '' }}>
                            <span class="ml-3 flex items-center text-sm">
                                <svg class="w-5 h-5 mr-2 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                </svg>
                                Dark
                            </span>
                        </label>
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 {{ auth()->user()->theme === 'auto' ? 'border-primary bg-primary/5' : 'border-gray-200' }}">
                            <input type="radio" name="theme" value="auto" class="w-4 h-4 text-primary" {{ auth()->user()->theme === 'auto' ? 'checked' : '' }}>
                            <span class="ml-3 flex items-center text-sm">
                                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                Auto (System)
                            </span>
                        </label>
                    </div>
                    <button type="submit"
                        class="w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 mt-4">
                        Save Theme
                    </button>
                </div>
            </form>
        </div>

        <!-- General Settings -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <div class="flex items-center mb-3 sm:mb-4">
                <div class="bg-primary/10 p-2 sm:p-2.5 lg:p-3 rounded-lg mr-3 sm:mr-4">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <h2 class="text-base sm:text-lg lg:text-xl font-bold text-gray-800">General Settings</h2>
            </div>
            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                <div class="space-y-3 sm:space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Application Name</label>
                        <input type="text" name="app_name" value="RUMU"
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Support Email</label>
                        <input type="email" name="support_email" value="support@rumu.com"
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">Timezone</label>
                        <select name="timezone"
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="Asia/Kolkata" selected>Asia/Kolkata (IST)</option>
                            <option value="UTC">UTC</option>
                            <option value="America/New_York">America/New_York (EST)</option>
                        </select>
                    </div>
                    <button type="submit"
                        class="w-full bg-primary hover:bg-primary/90 text-white font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Meta Conversions API -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <div class="flex items-center mb-3 sm:mb-4">
                <div class="bg-[#1877F2]/10 p-2 sm:p-2.5 lg:p-3 rounded-lg mr-3 sm:mr-4">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.25c-1.23 0-1.62.77-1.62 1.55V12h2.76l-.44 2.89h-2.32v6.99A10 10 0 0 0 22 12z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-base sm:text-lg lg:text-xl font-bold text-gray-800">Meta Lead Quality</h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Send CRM lead status updates back to Meta</p>
                </div>
                <span class="shrink-0 px-2.5 py-1 text-xs font-semibold rounded-full {{ $metaCapiConfigured ? 'bg-green-100 text-green-800' : ($metaCapiEnabled ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600') }}">
                    {{ $metaCapiConfigured ? 'On' : ($metaCapiEnabled ? 'On (not connected)' : 'Off') }}
                </span>
            </div>

            <form action="{{ route('admin.settings.meta-capi') }}" method="POST">
                @csrf
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <h4 class="font-semibold text-gray-800 text-sm">Conversions API Status</h4>
                        <p class="text-xs text-gray-500 mt-1">When on, lead status changes are sent to your Meta CRM dataset.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                        <input type="hidden" name="meta_capi_enabled" value="0">
                        <input type="checkbox" name="meta_capi_enabled" value="1" class="sr-only peer" {{ $metaCapiEnabled ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>
                <button type="submit"
                    class="w-full mt-4 bg-[#1877F2] hover:bg-[#166FE5] text-white font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0">
                    Save Meta Setting
                </button>
            </form>
        </div>

        <!-- Email Settings -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <div class="flex items-center mb-3 sm:mb-4">
                <div class="bg-blue-500/10 p-2 sm:p-2.5 lg:p-3 rounded-lg mr-3 sm:mr-4">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <h2 class="text-base sm:text-lg lg:text-xl font-bold text-gray-800">Email Settings</h2>
            </div>
            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                <div class="space-y-3 sm:space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">SMTP Host</label>
                        <input type="text" name="smtp_host" value="smtp.gmail.com"
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">SMTP Port</label>
                        <input type="number" name="smtp_port" value="587"
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">SMTP Username</label>
                        <input type="text" name="smtp_username" value=""
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 sm:mb-2">SMTP Password</label>
                        <input type="password" name="smtp_password" value=""
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0">
                        Save Email Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- System Information -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <div class="flex items-center mb-3 sm:mb-4">
                <div class="bg-green-500/10 p-2 sm:p-2.5 lg:p-3 rounded-lg mr-3 sm:mr-4">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-base sm:text-lg lg:text-xl font-bold text-gray-800">System Information</h2>
            </div>
            <div class="space-y-2 sm:space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <span class="text-gray-600 text-sm">Application Version</span>
                    <span class="font-semibold text-gray-800 text-sm">1.0.0</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <span class="text-gray-600 text-sm">PHP Version</span>
                    <span class="font-semibold text-gray-800 text-sm truncate ml-2">{{ PHP_VERSION }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <span class="text-gray-600 text-sm">Laravel Version</span>
                    <span class="font-semibold text-gray-800 text-sm">{{ app()->version() }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                    <span class="text-gray-600 text-sm">Database</span>
                    <span class="font-semibold text-gray-800 text-sm">MySQL</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-600 text-sm">Environment</span>
                    <span
                        class="px-2 py-1 text-xs font-semibold rounded-full {{ app()->environment('production') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ ucfirst(app()->environment()) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Cache & Maintenance -->
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-4 sm:p-5 lg:p-6">
            <div class="flex items-center mb-3 sm:mb-4">
                <div class="bg-purple-500/10 p-2 sm:p-2.5 lg:p-3 rounded-lg mr-3 sm:mr-4">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                </div>
                <h2 class="text-base sm:text-lg lg:text-xl font-bold text-gray-800">Cache & Maintenance</h2>
            </div>
            <div class="space-y-2.5 sm:space-y-3">
                <button
                    class="w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition flex items-center justify-center text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                    Clear Cache
                </button>
                <button
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition flex items-center justify-center text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                    Enable Maintenance Mode
                </button>
                <button
                    class="w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 sm:py-2.5 px-4 rounded-lg transition flex items-center justify-center text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Backup Database
                </button>
            </div>
        </div>
    </div>

    <script>
        // Auto-preview theme change
        document.querySelectorAll('input[name="theme"]').forEach(radio => {
            radio.addEventListener('change', function() {
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