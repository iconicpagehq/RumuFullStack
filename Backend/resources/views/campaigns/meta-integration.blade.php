@extends('layouts.app')

@section('title', 'Meta Integration - ' . $campaign->name)

@section('content')
    <div class="min-h-screen bg-gray-50">
        @if(Auth::user()->role === 'admin')
            @include('admin.partials.sidebar')
        @else
            @include('realtor.partials.sidebar')
        @endif

        <main class="lg:ml-64 min-h-screen">
            <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-10">
                <div class="px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 sm:space-x-4">
                            <button onclick="toggleMobileMenu()" class="lg:hidden text-gray-600 hover:text-primary p-2 -ml-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800">Meta Integration</h1>
                                </div>
                                <p class="text-gray-500 mt-1 text-sm sm:text-base">Campaign: {{ $campaign->name }}</p>
                            </div>
                        </div>
                        @include('realtor.partials.user-dropdown')
                    </div>
                </div>
            </header>

            <div class="p-4 sm:p-6 lg:p-8">
                <div class="max-w-6xl mx-auto space-y-6">

                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 animate-fade-in">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Back to campaign -->
                    <div class="mb-4">
                        <a href="{{ route('campaigns.show', $campaign) }}" class="text-primary hover:text-primary/80 text-sm font-medium inline-flex items-center transition">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Back to Campaign
                        </a>
                    </div>

                    @if($metaPages->contains('status', 'expired'))
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg shadow-sm">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Facebook connection expired</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>One or more of your Meta access tokens have expired or are invalid. Please reconnect your Facebook account to resume syncing.</p>
                                    </div>
                                    <div class="mt-4">
                                        <div class="-mx-2 -my-1.5 flex">
                                            <a href="{{ route('connect.facebook.redirect') }}" class="bg-red-100 px-3 py-2 rounded-md text-sm font-medium text-red-800 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-red-50 focus:ring-red-600 transition">
                                                Reconnect Facebook
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Column: Status Cards -->
                        <div class="lg:col-span-2 space-y-6">
                            
                            <!-- Connected Pages Card -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                        </svg>
                                        Facebook Pages
                                    </h3>
                                    @if($metaPages->isNotEmpty())
                                        <div class="flex items-center space-x-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ $metaPages->count() }} Connected
                                            </span>
                                            <a href="{{ route('connect.facebook.redirect') }}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                                                <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                Sync Facebook Pages
                                            </a>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Disconnected
                                        </span>
                                    @endif
                                </div>
                                <div class="p-6">
                                    @if($metaPages->isNotEmpty())
                                        <div class="space-y-4">
                                            @foreach($metaPages as $page)
                                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border {{ $selectedPage && $selectedPage->id === $page->id ? 'border-primary' : 'border-gray-200' }}">
                                                    <div>
                                                        <p class="text-sm font-semibold text-gray-900">{{ $page->page_name }}</p>
                                                        <p class="text-xs text-gray-500 font-mono mt-1">ID: {{ $page->page_id }}</p>
                                                    </div>
                                                    <div>
                                                        @if($page->status === 'expired')
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Expired</span>
                                                        @elseif($page->status === 'connected')
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Connected</span>
                                                        @else
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Disconnected</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-600 mb-4">You have not linked any Facebook pages yet. Connect your account in Settings.</p>
                                        <a href="{{ route('realtor.profile') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90">
                                            Go to Settings
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <!-- Lead Form Mapping Card -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Lead Form Mapping
                                    </h3>
                                    @if($formInfo)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $webhookHealth === 'Active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            Webhook: {{ $webhookHealth }}
                                        </span>
                                    @endif
                                </div>
                                <div class="p-6">
                                    @if($formInfo)
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-y-6 gap-x-4 mb-6">
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Form Name</p>
                                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $formInfo['form_name'] }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Form ID</p>
                                                <p class="mt-1 text-sm text-gray-900 font-mono">{{ $formInfo['form_id'] }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">API Health</p>
                                                @if($apiHealth)
                                                    @if($apiHealth['consecutive_failures'] > 0)
                                                        <p class="mt-1 text-sm text-red-600 font-medium flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                            Failing ({{ $apiHealth['consecutive_failures'] }} attempts)
                                                        </p>
                                                    @else
                                                        <p class="mt-1 text-sm text-green-600 font-medium flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                            Healthy
                                                        </p>
                                                    @endif
                                                @else
                                                    <p class="mt-1 text-sm text-gray-500">Unknown</p>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Last Webhook Received</p>
                                                <p class="mt-1 text-sm text-gray-900">
                                                    {{ $formInfo['last_webhook'] ? \Carbon\Carbon::parse($formInfo['last_webhook'])->diffForHumans() : 'Never' }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Last Manual Sync</p>
                                                <p class="mt-1 text-sm text-gray-900">
                                                    {{ $formInfo['last_sync'] ? \Carbon\Carbon::parse($formInfo['last_sync'])->diffForHumans() : 'Never' }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Last API Request</p>
                                                <p class="mt-1 text-sm text-gray-900">
                                                    @if($apiHealth && ($apiHealth['last_success'] || $apiHealth['last_failure']))
                                                        @php
                                                            $lastSuccess = $apiHealth['last_success'] ? \Carbon\Carbon::parse($apiHealth['last_success']) : null;
                                                            $lastFailure = $apiHealth['last_failure'] ? \Carbon\Carbon::parse($apiHealth['last_failure']) : null;
                                                            $latest = collect([$lastSuccess, $lastFailure])->filter()->max();
                                                        @endphp
                                                        {{ $latest ? $latest->diffForHumans() : 'Never' }}
                                                    @else
                                                        Never
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-100">
                                            <a href="{{ route('leads.import.meta.page', $campaign) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition">
                                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                Manual Sync / Change Form
                                            </a>
                                            <form action="{{ route('campaigns.meta-integration.disconnect-form', $campaign) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" onclick="return confirm('Are you sure you want to disconnect this lead form? Automatic syncing will stop.')" class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 transition">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                    </svg>
                                                    Disconnect Form
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <div class="text-center py-6">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                            </svg>
                                            <p class="text-sm text-gray-600 mb-4">No Meta Lead Form is currently mapped to this campaign.</p>
                                            <a href="{{ route('leads.import.meta.page', $campaign) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90">
                                                Connect Lead Form
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Conversions API (CAPI) Card -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-6">
                                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12c0-5.523-4.477-10-10-10z"/>
                                        </svg>
                                        Conversions API (CAPI)
                                    </h3>
                                    <div>
                                        @if($capiSettings && $capiSettings->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Inactive
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="p-6">
                                    @if($lastCapiEvent)
                                        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200 flex items-start space-x-4">
                                            <div class="flex-shrink-0 mt-1">
                                                @if($lastCapiEvent->status === 'success')
                                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                @elseif($lastCapiEvent->status === 'failed')
                                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                @else
                                                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                @endif
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="text-sm font-medium text-gray-900">Last Event Sent</h4>
                                                <p class="text-sm text-gray-500 mt-1">
                                                    <span class="font-semibold">{{ $lastCapiEvent->event_name }}</span> for Lead #{{ $lastCapiEvent->lead_id }}
                                                    &mdash; {{ $lastCapiEvent->sent_at ? $lastCapiEvent->sent_at->diffForHumans() : 'Pending' }}
                                                </p>
                                            </div>
                                        </div>
                                    @endif

                                    <form action="{{ route('campaigns.meta-integration.capi', $campaign) }}" method="POST" class="space-y-6">
                                        @csrf
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label for="pixel_id" class="block text-sm font-medium text-gray-700 mb-1">Dataset / Pixel ID <span class="text-red-500">*</span></label>
                                                <input type="text" name="pixel_id" id="pixel_id" required
                                                    value="{{ old('pixel_id', $capiSettings->pixel_id ?? '') }}"
                                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                                @error('pixel_id')
                                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            
                                            <div>
                                                <label for="access_token" class="block text-sm font-medium text-gray-700 mb-1">Access Token <span class="text-red-500">*</span></label>
                                                <input type="password" name="access_token" id="access_token"
                                                    placeholder="{{ $capiSettings && $capiSettings->access_token ? '••••••••••••••••••••••••' : 'Enter System User Access Token' }}"
                                                    {{ !$capiSettings || !$capiSettings->access_token ? 'required' : '' }}
                                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                                                <p class="text-xs text-gray-500 mt-1">Leave blank to keep existing token.</p>
                                                @error('access_token')
                                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="test_event_code" class="block text-sm font-medium text-gray-700 mb-1">Test Event Code (Optional)</label>
                                                <input type="text" name="test_event_code" id="test_event_code"
                                                    value="{{ old('test_event_code', $capiSettings->test_event_code ?? '') }}"
                                                    placeholder="e.g. TEST12345"
                                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm font-mono">
                                            </div>

                                            <div class="flex items-center mt-6">
                                                <input type="checkbox" name="is_active" id="is_active" value="1"
                                                    {{ old('is_active', $capiSettings->is_active ?? true) ? 'checked' : '' }}
                                                    class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                                                <label for="is_active" class="ml-2 block text-sm font-medium text-gray-900">
                                                    Enable Conversions API
                                                </label>
                                            </div>
                                        </div>

                                        <div class="pt-6 border-t border-gray-200">
                                            <h4 class="text-md font-medium text-gray-900 mb-4">Lead Status Mapping</h4>
                                            <p class="text-sm text-gray-500 mb-4">Map your CRM lead statuses to standard Meta Events. If left blank, changes to that status will not be sent to Meta.</p>
                                            
                                            @php
                                                $statuses = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
                                                $metaEvents = ['Contact', 'Schedule', 'SubmitApplication', 'Purchase', 'Lead', 'ViewContent'];
                                                $currentMapping = $capiSettings->status_mapping ?? [];
                                            @endphp
                                            
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                                                @foreach($statuses as $status)
                                                    <div class="flex items-center justify-between bg-white p-3 rounded shadow-sm border border-gray-100">
                                                        <span class="text-sm font-medium capitalize text-gray-700">{{ $status }}</span>
                                                        <svg class="w-4 h-4 text-gray-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                                        <select name="status_mapping[{{ $status }}]" class="block w-36 py-1.5 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                                                            <option value="">-- Ignore --</option>
                                                            @foreach($metaEvents as $metaEvent)
                                                                <option value="{{ $metaEvent }}" {{ (old("status_mapping.{$status}", $currentMapping[$status] ?? '') == $metaEvent) ? 'selected' : '' }}>
                                                                    {{ $metaEvent }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="pt-4 flex justify-end">
                                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                                Save CAPI Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Stats & Timeline -->
                        <div class="space-y-6">
                            
                            <!-- Statistics Card -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100">
                                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        Statistics
                                    </h3>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Imported Leads</p>
                                        <p class="text-3xl font-bold text-primary mt-1">{{ number_format($stats['total_imported']) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Last Lead Imported</p>
                                        <p class="text-sm text-gray-900 mt-1">
                                            {{ $stats['last_import_time'] ? \Carbon\Carbon::parse($stats['last_import_time'])->diffForHumans() : 'Never' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Activity Timeline -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100">
                                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Activity Timeline
                                    </h3>
                                </div>
                                <div class="p-6">
                                    @if($logs->isEmpty())
                                        <p class="text-sm text-gray-500 text-center py-4">No recent integration activity.</p>
                                    @else
                                        <div class="flow-root">
                                            <ul role="list" class="-mb-8">
                                                @foreach($logs as $index => $log)
                                                    <li>
                                                        <div class="relative pb-8">
                                                            @if($index !== count($logs) - 1)
                                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                                            @endif
                                                            <div class="relative flex space-x-3">
                                                                <div>
                                                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white 
                                                                        @if($log->type === 'success') bg-green-500 
                                                                        @elseif($log->type === 'error') bg-red-500 
                                                                        @elseif($log->type === 'warning') bg-yellow-500 
                                                                        @else bg-blue-500 @endif">
                                                                        
                                                                        @if($log->event_type === 'webhook')
                                                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                                            </svg>
                                                                        @elseif($log->event_type === 'manual_sync')
                                                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                                            </svg>
                                                                        @elseif($log->event_type === 'connected')
                                                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"></path>
                                                                            </svg>
                                                                        @else
                                                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                            </svg>
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                                    <div>
                                                                        <p class="text-sm text-gray-700">{{ $log->message }}</p>
                                                                    </div>
                                                                    <div class="text-right text-xs whitespace-nowrap text-gray-500">
                                                                        <time datetime="{{ $log->created_at }}">{{ $log->created_at->diffForHumans() }}</time>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
@endsection
