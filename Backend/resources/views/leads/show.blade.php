@extends('layouts.app')

@section('title', 'Lead Details - ' . $lead->name)

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    @if(Auth::user()->role === 'admin')
        @include('admin.partials.sidebar')
    @else
        @include('realtor.partials.sidebar')
    @endif

    <main class="lg:ml-64 min-h-screen">
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-10">
            <div class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4">
                {{-- Desktop & Tablet Layout --}}
                <div class="hidden sm:flex items-center justify-between gap-3">
                    {{-- Left: Title --}}
                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                        <button onclick="toggleMobileMenu()" class="lg:hidden text-gray-600 dark:text-gray-300 hover:text-primary p-2 -ml-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <h1 class="text-lg lg:text-xl font-bold text-gray-800 dark:text-white truncate">{{ $lead->name }}</h1>
                            <p class="text-gray-500 dark:text-gray-400 text-xs truncate">
                                Campaign: <a href="{{ route('campaigns.show', $lead->campaign) }}" class="text-primary hover:underline">{{ $lead->campaign->name }}</a>
                            </p>
                        </div>
                    </div>
                    
                    {{-- Right: Actions --}}
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('leads.edit', $lead) }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium py-2 px-4 rounded-lg transition text-sm whitespace-nowrap">
                            Edit Lead
                        </a>
                        <a href="{{ route('campaigns.show', $lead->campaign) }}" class="bg-primary hover:bg-primary/90 text-white font-medium py-2 px-4 rounded-lg transition text-sm whitespace-nowrap">
                            Back
                        </a>
                        @include('realtor.partials.user-dropdown')
                    </div>
                </div>
                
                {{-- Mobile Layout --}}
                <div class="flex sm:hidden flex-col gap-2">
                    {{-- Row 1: Menu + Title + Avatar --}}
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center space-x-2 min-w-0 flex-1">
                            <button onclick="toggleMobileMenu()" class="text-gray-600 dark:text-gray-300 hover:text-primary p-1.5 -ml-1.5 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <h1 class="text-base font-bold text-gray-800 dark:text-white truncate">{{ $lead->name }}</h1>
                        </div>
                        {{-- Mobile avatar only --}}
                        <div class="shrink-0">
                            @if(Auth::user()->avatar_url)
                                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-8 h-8 rounded-full object-cover border border-primary/20">
                            @else
                                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white font-bold text-sm border border-primary/20">
                                    {{ Auth::user()->avatar_initials }}
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Row 2: Campaign info --}}
                    <p class="text-gray-500 dark:text-gray-400 text-xs truncate">
                        Campaign: <a href="{{ route('campaigns.show', $lead->campaign) }}" class="text-primary hover:underline">{{ $lead->campaign->name }}</a>
                    </p>
                    
                    {{-- Row 3: Action Buttons --}}
                    <div class="flex items-center gap-2 pt-1">
                        <a href="{{ route('leads.edit', $lead) }}" class="flex-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium py-2 px-3 rounded-lg transition text-sm text-center">
                            Edit Lead
                        </a>
                        <a href="{{ route('campaigns.show', $lead->campaign) }}" class="flex-1 bg-primary hover:bg-primary/90 text-white font-medium py-2 px-3 rounded-lg transition text-sm text-center">
                            Back
                        </a>
                    </div>
                </div>
            </div>
        </header>

        @php
            $customFields = $lead->custom_fields ?? [];
            
            // Check which sections have data
            $hasClientDetails = !empty(array_filter([
                $customFields['client_type'] ?? null,
                $customFields['budget_range'] ?? null,
                $customFields['purpose'] ?? null,
                $customFields['family_size'] ?? null
            ]));
            
            $hasPropertyReq = !empty(array_filter([
                $customFields['property_type'] ?? null,
                $customFields['configuration'] ?? null,
                $customFields['preferred_location'] ?? null,
                $customFields['project_name'] ?? null,
                $customFields['construction_status'] ?? null
            ]));
            
            $hasLocationVisit = !empty(array_filter([
                $customFields['city'] ?? null,
                $customFields['site_visit_required'] ?? null,
                $customFields['landmarks'] ?? null
            ]));
            
            $hasQualification = !empty(array_filter([
                $customFields['lead_source'] ?? null,
                $customFields['lead_priority'] ?? null,
                $customFields['buying_timeline'] ?? null,
                $customFields['funding_type'] ?? null
            ]));
        @endphp

        <div class="p-3 sm:p-4 lg:p-6">
            @include('realtor.partials.subscription-suspended-banner')

            @if($lead->inventoryUnit)
            <div class="mb-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6 border-l-4 border-primary">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white mb-2">Assigned Inventory Unit</h3>
                <p class="text-gray-700 dark:text-gray-300 font-medium">{{ $lead->inventoryUnit->fullPath() }}</p>
                <div class="flex flex-wrap gap-3 mt-2 text-sm text-gray-500">
                    @if($lead->inventoryUnit->layout)
                        <span>Layout: {{ strtoupper(str_replace('_', ' ', $lead->inventoryUnit->layout)) }}</span>
                    @endif
                    @if($lead->inventoryUnit->base_price)
                        <span>Price: ₹{{ number_format($lead->inventoryUnit->base_price) }}</span>
                    @endif
                    <span>@include('inventory.partials.availability-badge', ['status' => $lead->inventoryUnit->availability_status])</span>
                </div>
            </div>
            @endif

            @if($lead->meta_lead_id)
                <div class="mb-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-white">Meta sync</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Lead ID: {{ $lead->meta_lead_id }}</p>
                            @if($metaCapiConfigured)
                                <p class="text-xs text-green-600 dark:text-green-400 mt-1">Reverse signal active — status changes are sent to Meta.</p>
                            @else
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">CAPI not configured on server.</p>
                            @endif
                        </div>
                        @can('update', $lead)
                            @if($metaCapiConfigured)
                                <form method="POST" action="{{ route('leads.meta-capi.resend', $lead) }}" class="shrink-0">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium bg-primary/10 hover:bg-primary/20 text-primary px-3 py-2 rounded-lg transition">
                                        Resend current status to Meta
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                    @if($lead->metaCapiEvents->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                        <th class="py-2 pr-4">Event</th>
                                        <th class="py-2 pr-4">CRM status</th>
                                        <th class="py-2 pr-4">Sent</th>
                                        <th class="py-2">Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lead->metaCapiEvents as $log)
                                        <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                            <td class="py-2 pr-4 text-gray-800 dark:text-gray-200">{{ $log->event_name }}</td>
                                            <td class="py-2 pr-4 capitalize text-gray-600 dark:text-gray-300">{{ $log->crm_status }}</td>
                                            <td class="py-2 pr-4 text-gray-500 dark:text-gray-400">{{ $log->sent_at?->format('M j, Y H:i') ?? '—' }}</td>
                                            <td class="py-2">
                                                @if($log->success)
                                                    <span class="text-green-600 dark:text-green-400">OK</span>
                                                @else
                                                    <span class="text-red-600 dark:text-red-400" title="{{ $log->error_message }}">Failed</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400">No Meta events sent yet for this lead.</p>
                    @endif
                </div>
            @endif

            <!-- Status Update Section (Mobile Top) -->
            <div class="lg:hidden mb-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white mb-3">Update Status</h3>
                    <form method="POST" action="{{ route('leads.update', $lead) }}" id="mobile-status-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $lead->name }}">
                        <input type="hidden" name="email" value="{{ $lead->email }}">
                        <input type="hidden" name="phone" value="{{ $lead->phone }}">
                        <input type="hidden" name="company" value="{{ $lead->company }}">

                        <div class="flex flex-wrap gap-2">
                            @php
                                $statuses = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
                                $statusColors = [
                                    'new' => 'bg-blue-500 text-white border-blue-500',
                                    'contacted' => 'bg-yellow-500 text-white border-yellow-500',
                                    'qualified' => 'bg-purple-500 text-white border-purple-500',
                                    'proposal' => 'bg-indigo-500 text-white border-indigo-500',
                                    'negotiation' => 'bg-orange-500 text-white border-orange-500',
                                    'won' => 'bg-green-500 text-white border-green-500',
                                    'lost' => 'bg-red-500 text-white border-red-500',
                                ];
                                $statusColorsInactive = [
                                    'new' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-700',
                                    'contacted' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700',
                                    'qualified' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 border-purple-200 dark:border-purple-700',
                                    'proposal' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700',
                                    'negotiation' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 border-orange-200 dark:border-orange-700',
                                    'won' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-700',
                                    'lost' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-700',
                                ];
                            @endphp
                            @foreach($statuses as $status)
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="{{ $status }}" 
                                        {{ $lead->status === $status ? 'checked' : '' }}
                                        onchange="document.getElementById('mobile-status-form').submit()"
                                        class="hidden peer">
                                    <span class="px-3 py-1.5 text-xs font-medium rounded-lg border capitalize transition
                                        {{ $lead->status === $status ? $statusColors[$status] : $statusColorsInactive[$status] }}">
                                        {{ $status }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cards Grid - 4x1 Desktop, 2x2 Mobile -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                
                <!-- Card 1: Basic Information -->
                <div onclick="openModal('modal-basic')" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6 cursor-pointer hover:shadow-xl transition group">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 bg-primary/10 dark:bg-primary/20 rounded-full flex items-center justify-center mb-3 group-hover:bg-primary/20 dark:group-hover:bg-primary/30 transition">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-gray-800 dark:text-white">Basic Information</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Name, Email, Phone</p>
                        <div class="mt-2 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-600 dark:text-gray-300">
                            {{ $lead->email ? '✓ Email' : '' }} {{ $lead->phone ? '✓ Phone' : '' }}
                        </div>
                    </div>
                </div>

                <!-- Card 2: Buyer / Client Details -->
                <div onclick="openModal('modal-client')" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6 cursor-pointer hover:shadow-xl transition group {{ !$hasClientDetails ? 'opacity-60' : '' }}">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-3 group-hover:bg-green-200 dark:group-hover:bg-green-800/50 transition">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-gray-800 dark:text-white">Client Details</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Type, Budget, Purpose</p>
                        @if(!$hasClientDetails)
                            <span class="mt-2 text-xs text-orange-500 dark:text-orange-400">No data</span>
                        @else
                            <span class="mt-2 text-xs text-green-600 dark:text-green-400">✓ Data available</span>
                        @endif
                    </div>
                </div>

                <!-- Card 3: Property Requirement -->
                <div onclick="openModal('modal-property')" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6 cursor-pointer hover:shadow-xl transition group {{ !$hasPropertyReq ? 'opacity-60' : '' }}">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-3 group-hover:bg-blue-200 dark:group-hover:bg-blue-800/50 transition">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-gray-800 dark:text-white">Property Requirement</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Type, Configuration</p>
                        @if(!$hasPropertyReq)
                            <span class="mt-2 text-xs text-orange-500 dark:text-orange-400">No data</span>
                        @else
                            <span class="mt-2 text-xs text-blue-600 dark:text-blue-400">✓ Data available</span>
                        @endif
                    </div>
                </div>

                <!-- Card 4: Lead Qualification -->
                <div onclick="openModal('modal-qualification')" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6 cursor-pointer hover:shadow-xl transition group {{ !$hasQualification ? 'opacity-60' : '' }}">
                    <div class="flex flex-col items-center text-center">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mb-3 group-hover:bg-purple-200 dark:group-hover:bg-purple-800/50 transition">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-gray-800 dark:text-white">Lead Qualification</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Source, Priority, Timeline</p>
                        @if(!$hasQualification)
                            <span class="mt-2 text-xs text-orange-500 dark:text-orange-400">No data</span>
                        @else
                            <span class="mt-2 text-xs text-purple-600 dark:text-purple-400">✓ Data available</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Location & Visit Card (Full Width) -->
            <div class="mb-6">
                <div onclick="openModal('modal-location')" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6 cursor-pointer hover:shadow-xl transition group {{ !$hasLocationVisit ? 'opacity-60' : '' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mr-4 group-hover:bg-orange-200 dark:group-hover:bg-orange-800/50 transition">
                                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-gray-800 dark:text-white">Location & Visit</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">City, Site Visit, Landmarks</p>
                            </div>
                        </div>
                        <div class="text-right">
                            @if(!$hasLocationVisit)
                                <span class="text-xs text-orange-500 dark:text-orange-400">No data</span>
                            @else
                                <span class="text-xs text-orange-600 dark:text-orange-400">✓ Data available</span>
                            @endif
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 mt-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop Status Update -->
            <div class="hidden lg:block mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h3 class="text-base font-bold text-gray-800 dark:text-white mb-4">Update Status</h3>
                    <form method="POST" action="{{ route('leads.update', $lead) }}" id="desktop-status-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $lead->name }}">
                        <input type="hidden" name="email" value="{{ $lead->email }}">
                        <input type="hidden" name="phone" value="{{ $lead->phone }}">
                        <input type="hidden" name="company" value="{{ $lead->company }}">

                        <div class="flex flex-wrap gap-3">
                            @foreach($statuses as $status)
                                <label class="cursor-pointer">
                                    <input type="radio" name="status" value="{{ $status }}" 
                                        {{ $lead->status === $status ? 'checked' : '' }}
                                        onchange="document.getElementById('desktop-status-form').submit()"
                                        class="hidden peer">
                                    <span class="px-4 py-2 text-sm font-medium rounded-lg border capitalize transition
                                        {{ $lead->status === $status ? $statusColors[$status] : $statusColorsInactive[$status] }}">
                                        {{ $status }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </form>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-bold text-gray-800 dark:text-white mb-3 sm:mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Activity & Notes</h3>
                
                <!-- Add Note Form -->
                <div class="mb-6 sm:mb-8">
                    <form method="POST" action="{{ route('leads.notes.store', $lead) }}">
                        @csrf
                        <div class="mb-3">
                            <textarea name="note" rows="2" required
                                class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                placeholder="Add a note about this lead..."></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-primary hover:bg-primary/90 text-white font-medium py-1.5 sm:py-2 px-3 sm:px-4 rounded-lg transition text-xs sm:text-sm">
                                Add Note
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Notes Trail -->
                <div class="space-y-4 sm:space-y-6">
                    @forelse($lead->notes as $note)
                        <div class="flex space-x-2 sm:space-x-3">
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center text-gray-600 dark:text-gray-300 font-bold text-[10px] sm:text-xs">
                                    {{ strtoupper(substr($note->user->name, 0, 1)) }}
                                </div>
                            </div>
                            <div class="flex-grow min-w-0">
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 sm:p-4 border border-gray-100 dark:border-gray-600">
                                    <div class="flex justify-between items-start mb-1 gap-2">
                                        <span class="font-semibold text-xs sm:text-sm text-gray-900 dark:text-white truncate">{{ $note->user->name }}</span>
                                        <span class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 shrink-0">{{ $note->created_at->format('M d, h:i A') }}</span>
                                    </div>
                                    <p class="text-gray-700 dark:text-gray-300 text-xs sm:text-sm whitespace-pre-line">{{ $note->note }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 sm:py-6 text-gray-500 dark:text-gray-400 text-xs sm:text-sm italic">
                            No notes yet. Start the conversation!
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modals -->

<!-- Modal 1: Basic Information -->
<div id="modal-basic" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-80 transition-opacity" aria-hidden="true" onclick="closeModal('modal-basic')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-primary/10 dark:bg-primary/20 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Basic Information</h3>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium">{{ $lead->name }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</label>
                            <span class="px-2 py-1 inline-flex text-xs font-semibold rounded-full bg-{{ $lead->status_color }}-100 dark:bg-{{ $lead->status_color }}-900/30 text-{{ $lead->status_color }}-800 dark:text-{{ $lead->status_color }}-300">
                                {{ ucfirst($lead->status) }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Added On</label>
                            <p class="text-gray-800 dark:text-gray-200 text-sm">{{ $lead->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</label>
                        @if($lead->email)
                            <a href="mailto:{{ $lead->email }}" class="text-primary hover:underline">{{ $lead->email }}</a>
                        @else
                            <p class="text-gray-400 dark:text-gray-500 italic text-sm">Not provided</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Phone</label>
                        @if($lead->phone)
                            <a href="tel:{{ $lead->phone }}" class="text-gray-800 dark:text-gray-200 hover:text-primary">{{ $lead->phone }}</a>
                        @else
                            <p class="text-gray-400 dark:text-gray-500 italic text-sm">Not provided</p>
                        @endif
                    </div>
                    @if($lead->company)
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Company</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium">{{ $lead->company }}</p>
                    </div>
                    @endif
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeModal('modal-basic')" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Client Details -->
<div id="modal-client" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-80 transition-opacity" aria-hidden="true" onclick="closeModal('modal-client')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Buyer / Client Details</h3>
                </div>
                @if(!$hasClientDetails)
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">No client details available.</p>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if(!empty($customFields['client_type']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Client Type</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium capitalize">{{ str_replace('_', ' ', $customFields['client_type']) }}</p>
                    </div>
                    @endif
                    @if(!empty($customFields['budget_range']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Budget Range</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium">
                            @php
                                $budgetLabels = [
                                    'below_50l' => 'Below ₹50L', '50l_75l' => '₹50L – ₹75L', '75l_1cr' => '₹75L – ₹1Cr',
                                    '1cr_1.5cr' => '₹1Cr – ₹1.5Cr', '1.5cr_2cr' => '₹1.5Cr – ₹2Cr', '2cr_3cr' => '₹2Cr – ₹3Cr',
                                    '3cr_5cr' => '₹3Cr – ₹5Cr', 'above_5cr' => 'Above ₹5Cr',
                                ];
                            @endphp
                            {{ $budgetLabels[$customFields['budget_range']] ?? $customFields['budget_range'] }}
                        </p>
                    </div>
                    @endif
                    @if(!empty($customFields['purpose']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Purpose</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium capitalize">{{ str_replace('_', ' ', $customFields['purpose']) }}</p>
                    </div>
                    @endif
                    @if(!empty($customFields['family_size']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Family Size</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium">{{ $customFields['family_size'] }} People</p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeModal('modal-client')" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: PropertyRequirement -->
<div id="modal-property" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-80 transition-opacity" aria-hidden="true" onclick="closeModal('modal-property')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Property Requirement</h3>
                </div>
                @if(!$hasPropertyReq)
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">No property requirements available.</p>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if(!empty($customFields['property_type']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Property Type</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium capitalize">{{ $customFields['property_type'] }}</p>
                    </div>
                    @endif
                    @if(!empty($customFields['configuration']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Configuration</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium uppercase">{{ str_replace('_', ' ', $customFields['configuration']) }}</p>
                    </div>
                    @endif
                    @if(!empty($customFields['preferred_location']))
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Preferred Location</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium">{{ $customFields['preferred_location'] }}</p>
                    </div>
                    @endif
                    @if(!empty($customFields['project_name']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Project Name</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium">{{ $customFields['project_name'] }}</p>
                    </div>
                    @endif
                    @if(!empty($customFields['construction_status']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Construction Status</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium capitalize">{{ str_replace('_', ' ', $customFields['construction_status']) }}</p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeModal('modal-property')" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 4: Lead Qualification -->
<div id="modal-qualification" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-80 transition-opacity" aria-hidden="true" onclick="closeModal('modal-qualification')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Lead Qualification</h3>
                </div>
                @if(!$hasQualification)
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">No qualification data available.</p>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if(!empty($customFields['lead_source']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lead Source</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium capitalize">{{ str_replace('_', ' ', $customFields['lead_source']) }}</p>
                    </div>
                    @endif
                    @if(!empty($customFields['lead_priority']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lead Priority</label>
                        <span class="px-2 py-1 inline-flex text-xs font-semibold rounded-full
                            @php
                                $priorityColor = match($customFields['lead_priority']) {
                                    'hot' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
                                    'warm' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300',
                                    'cold' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
                                    default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'
                                };
                            @endphp
                            {{ $priorityColor }}">
                            {{ ucfirst($customFields['lead_priority']) }}
                        </span>
                    </div>
                    @endif
                    @if(!empty($customFields['buying_timeline']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Buying Timeline</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium">
                            @php
                                $timelineLabels = [
                                    'immediate' => 'Immediate', '1_3_months' => '1-3 Months',
                                    '3_6_months' => '3-6 Months', '6_12_months' => '6-12 Months', 'just_browsing' => 'Just Browsing',
                                ];
                            @endphp
                            {{ $timelineLabels[$customFields['buying_timeline']] ?? str_replace('_', ' ', $customFields['buying_timeline']) }}
                        </p>
                    </div>
                    @endif
                    @if(!empty($customFields['funding_type']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Funding Type</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium capitalize">{{ str_replace('_', ' ', $customFields['funding_type']) }}</p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeModal('modal-qualification')" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 5: Location & Visit -->
<div id="modal-location" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-80 transition-opacity" aria-hidden="true" onclick="closeModal('modal-location')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Location & Visit</h3>
                </div>
                @if(!$hasLocationVisit)
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">No location data available.</p>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if(!empty($customFields['city']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">City</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium">{{ $customFields['city'] }}</p>
                    </div>
                    @endif
                    @if(!empty($customFields['site_visit_required']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Site Visit Required</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium capitalize">{{ $customFields['site_visit_required'] }}</p>
                    </div>
                    @endif
                    @if(!empty($customFields['preferred_visit_date']))
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Preferred Visit Date</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium">{{ \Carbon\Carbon::parse($customFields['preferred_visit_date'])->format('M d, Y') }}</p>
                    </div>
                    @endif
                    @if(!empty($customFields['landmarks']))
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nearby Landmarks</label>
                        <p class="text-gray-800 dark:text-gray-200 font-medium">{{ $customFields['landmarks'] }}</p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeModal('modal-location')" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary/90 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ['modal-basic', 'modal-client', 'modal-property', 'modal-qualification', 'modal-location'].forEach(function(modalId) {
                closeModal(modalId);
            });
        }
    });
</script>
@endsection
