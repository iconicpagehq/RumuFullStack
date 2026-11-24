@extends('layouts.app')

@section('title', 'Login - Rumu App')

@section('content')
    <div class="min-h-screen gradient-bg flex items-center justify-center py-6 sm:py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6 sm:space-y-8 animate-fade-in">
            <!-- Logo/Brand -->
            <div class="text-center">
                <img src="https://iconicpages.com/wp-content/uploads/2025/12/rumu-1.png" alt="RUMU Logo" class="h-20 mx-auto mb-4">
              
            </div>

            <!-- Login Card -->
            <div class="glass-effect rounded-2xl shadow-2xl p-8 hover-lift">
               
                @if ($errors->any())
                    <div class="bg-red-500/20 border border-red-500 text-white px-4 py-3 rounded-lg mb-4">
                        <ul class="list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <a href="{{ route('auth.google.redirect') }}"
                    class="w-full bg-white text-gray-800 font-semibold py-3 px-4 rounded-lg transition duration-300 transform hover:scale-105 shadow-lg flex items-center justify-center gap-3">
                    <svg class="w-5 h-5" viewBox="0 0 48 48" aria-hidden="true">
                        <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.654 32.659 29.195 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.047 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917Z"/>
                        <path fill="#FF3D00" d="M6.306 14.691 12.863 19.5C14.635 15.108 18.959 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.047 6.053 29.268 4 24 4c-7.682 0-14.346 4.326-17.694 10.691Z"/>
                        <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.197l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.174 0-9.618-3.317-11.289-7.946l-6.51 5.015C9.514 39.556 16.227 44 24 44Z"/>
                        <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.03 12.03 0 0 1-4.084 5.565l.003-.002 6.19 5.238C36.98 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917Z"/>
                    </svg>
                    Continue with Google
                </a>

                <a href="{{ route('auth.facebook.redirect') }}"
                    class="w-full mt-3 bg-[#1877F2] hover:bg-[#166FE5] text-white font-semibold py-3 px-4 rounded-lg transition duration-300 transform hover:scale-105 shadow-lg flex items-center justify-center gap-3">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">
                        <path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.25c-1.23 0-1.62.77-1.62 1.55V12h2.76l-.44 2.89h-2.32v6.99A10 10 0 0 0 22 12z"/>
                    </svg>
                    Continue with Facebook
                </a>

                <div class="flex items-center gap-3 my-6">
                    <div class="h-px bg-white/20 flex-1"></div>
                    <span class="text-white/70 text-sm">or</span>
                    <div class="h-px bg-white/20 flex-1"></div>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-white mb-2">Email Address</label>
                        <input id="email" name="email" type="email" required value="{{ old('email') }}"
                            class="w-full px-4 py-3 bg-white/10 border border-white/30 rounded-lg text-white placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition"
                            placeholder="Enter your email">
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-white mb-2">Password</label>
                        <div class="relative">
                            <input id="password" name="password" type="password" required
                                class="w-full px-4 py-3 pr-12 bg-white/10 border border-white/30 rounded-lg text-white placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition"
                                placeholder="Enter your password">
                            <button type="button"
                                class="absolute inset-y-0 right-0 flex items-center justify-center w-12 text-white/70 hover:text-white focus:outline-none focus:ring-2 focus:ring-secondary rounded-r-lg touch-manipulation"
                                aria-label="Toggle password visibility"
                                aria-pressed="false"
                                data-password-toggle="password">
                                <svg data-icon="eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="w-5 h-5">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg data-icon="eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="w-5 h-5 hidden">
                                    <path d="M10.5 10.5a2 2 0 0 0 2.83 2.83"></path>
                                    <path d="M9.36 5.72A10.94 10.94 0 0 1 12 5c6.5 0 10 7 10 7a18.18 18.18 0 0 1-2.16 3.19"></path>
                                    <path d="M6.61 6.61A18.46 18.46 0 0 0 2 12s3.5 7 10 7a10.94 10.94 0 0 0 3.28-.5"></path>
                                    <path d="M2 2l20 20"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox"
                            class="h-4 w-4 text-primary focus:ring-secondary border-white/30 rounded">
                        <label for="remember" class="ml-2 block text-sm text-white">
                            Remember me
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="w-full bg-secondary hover:bg-white text-primary font-bold py-3 px-4 rounded-lg transition duration-300 transform hover:scale-105 shadow-lg">
                        Sign In
                    </button>
                </form>

                <!-- Register Link -->
                <div class="mt-6 text-center">
                    <p class="text-white text-sm">
                        Don't have an account?
                        <a href="{{ route('register') }}" class="text-secondary hover:text-white font-semibold transition">
                            Register as Realtor
                        </a>
                    </p>
                    <p class="mt-3 text-white/70 text-sm">
                        <a href="{{ route('api-docs') }}" target="_blank" rel="noopener noreferrer"
                            class="hover:text-secondary transition underline">
                            API Docs
                        </a>
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-white/70 text-sm">
                © 2026 Rumu App. All rights reserved.
            </p>
            <p class="text-center text-white/60 text-xs mt-2">
                <a href="{{ route('privacy-policy') }}" class="hover:text-secondary transition underline">Privacy Policy</a>
                <span class="mx-2">·</span>
                <a href="{{ route('terms-of-service') }}" class="hover:text-secondary transition underline">Terms of Service</a>
                <span class="mx-2">·</span>
                <a href="{{ route('data-deletion') }}" class="hover:text-secondary transition underline">Data Deletion</a>
            </p>
        </div>
    </div>
@endsection