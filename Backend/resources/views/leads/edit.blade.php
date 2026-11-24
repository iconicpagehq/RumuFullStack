@extends('layouts.app')

@section('title', 'Edit Lead - ' . $lead->name)

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
                            <button onclick="toggleMobileMenu()"
                                class="lg:hidden text-gray-600 hover:text-primary p-2 -ml-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <div>
                                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800">Edit Lead</h1>
                                <p class="text-gray-500 mt-1 text-sm sm:text-base hidden sm:block">Update lead details</p>
                            </div>
                        </div>
                        @include('realtor.partials.user-dropdown')
                    </div>
                </div>
            </header>

            <div class="p-4 sm:p-6 lg:p-8">
                @include('realtor.partials.subscription-suspended-banner')

                <div class="max-w-5xl mx-auto">
                    <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                        @php
                            $customFields = $lead->custom_fields ?? [];
                        @endphp

                        <form method="POST" action="{{ route('leads.update', $lead) }}">
                            @csrf
                            @method('PUT')

                            <!-- Basic Information -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Basic Information
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Name -->
                                    <div>
                                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Lead Name *</label>
                                        <input type="text" name="name" id="name" value="{{ old('name', $lead->name) }}" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                                        <input type="email" name="email" id="email" value="{{ old('email', $lead->email) }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>

                                    <!-- Phone -->
                                    <div>
                                        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                                        <input type="text" name="phone" id="phone" value="{{ old('phone', $lead->phone) }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>

                                    <!-- Company -->
                                    <div>
                                        <label for="company" class="block text-sm font-semibold text-gray-700 mb-2">Company Name</label>
                                        <input type="text" name="company" id="company"
                                            value="{{ old('company', $lead->company) }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                </div>
                            </div>

                            <!-- Buyer / Client Details -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    Buyer / Client Details
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Client Type -->
                                    <div>
                                        <label for="client_type" class="block text-sm font-semibold text-gray-700 mb-2">Client Type</label>
                                        <select name="custom_fields[client_type]" id="client_type"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Select Client Type</option>
                                            <option value="buyer" {{ old('custom_fields.client_type', $customFields['client_type'] ?? '') == 'buyer' ? 'selected' : '' }}>Buyer</option>
                                            <option value="seller" {{ old('custom_fields.client_type', $customFields['client_type'] ?? '') == 'seller' ? 'selected' : '' }}>Seller</option>
                                            <option value="investor" {{ old('custom_fields.client_type', $customFields['client_type'] ?? '') == 'investor' ? 'selected' : '' }}>Investor</option>
                                            <option value="tenant" {{ old('custom_fields.client_type', $customFields['client_type'] ?? '') == 'tenant' ? 'selected' : '' }}>Tenant</option>
                                        </select>
                                    </div>

                                    <!-- Budget Range -->
                                    <div>
                                        <label for="budget_range" class="block text-sm font-semibold text-gray-700 mb-2">Budget Range</label>
                                        <select name="custom_fields[budget_range]" id="budget_range"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Select Budget Range</option>
                                            <option value="below_50l" {{ old('custom_fields.budget_range', $customFields['budget_range'] ?? '') == 'below_50l' ? 'selected' : '' }}>Below ₹50L</option>
                                            <option value="50l_75l" {{ old('custom_fields.budget_range', $customFields['budget_range'] ?? '') == '50l_75l' ? 'selected' : '' }}>₹50L – ₹75L</option>
                                            <option value="75l_1cr" {{ old('custom_fields.budget_range', $customFields['budget_range'] ?? '') == '75l_1cr' ? 'selected' : '' }}>₹75L – ₹1Cr</option>
                                            <option value="1cr_1.5cr" {{ old('custom_fields.budget_range', $customFields['budget_range'] ?? '') == '1cr_1.5cr' ? 'selected' : '' }}>₹1Cr – ₹1.5Cr</option>
                                            <option value="1.5cr_2cr" {{ old('custom_fields.budget_range', $customFields['budget_range'] ?? '') == '1.5cr_2cr' ? 'selected' : '' }}>₹1.5Cr – ₹2Cr</option>
                                            <option value="2cr_3cr" {{ old('custom_fields.budget_range', $customFields['budget_range'] ?? '') == '2cr_3cr' ? 'selected' : '' }}>₹2Cr – ₹3Cr</option>
                                            <option value="3cr_5cr" {{ old('custom_fields.budget_range', $customFields['budget_range'] ?? '') == '3cr_5cr' ? 'selected' : '' }}>₹3Cr – ₹5Cr</option>
                                            <option value="above_5cr" {{ old('custom_fields.budget_range', $customFields['budget_range'] ?? '') == 'above_5cr' ? 'selected' : '' }}>Above ₹5Cr</option>
                                        </select>
                                    </div>

                                    <!-- Purpose -->
                                    <div>
                                        <label for="purpose" class="block text-sm font-semibold text-gray-700 mb-2">Purpose</label>
                                        <select name="custom_fields[purpose]" id="purpose"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Select Purpose</option>
                                            <option value="self_use" {{ old('custom_fields.purpose', $customFields['purpose'] ?? '') == 'self_use' ? 'selected' : '' }}>Self-use</option>
                                            <option value="investment" {{ old('custom_fields.purpose', $customFields['purpose'] ?? '') == 'investment' ? 'selected' : '' }}>Investment</option>
                                            <option value="rental" {{ old('custom_fields.purpose', $customFields['purpose'] ?? '') == 'rental' ? 'selected' : '' }}>Rental</option>
                                        </select>
                                    </div>

                                    <!-- Family Size -->
                                    <div>
                                        <label for="family_size" class="block text-sm font-semibold text-gray-700 mb-2">Family Size (Optional)</label>
                                        <select name="custom_fields[family_size]" id="family_size"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Select Family Size</option>
                                            <option value="1" {{ old('custom_fields.family_size', $customFields['family_size'] ?? '') == '1' ? 'selected' : '' }}>1 Person</option>
                                            <option value="2" {{ old('custom_fields.family_size', $customFields['family_size'] ?? '') == '2' ? 'selected' : '' }}>2 People</option>
                                            <option value="3" {{ old('custom_fields.family_size', $customFields['family_size'] ?? '') == '3' ? 'selected' : '' }}>3 People</option>
                                            <option value="4" {{ old('custom_fields.family_size', $customFields['family_size'] ?? '') == '4' ? 'selected' : '' }}>4 People</option>
                                            <option value="5" {{ old('custom_fields.family_size', $customFields['family_size'] ?? '') == '5' ? 'selected' : '' }}>5 People</option>
                                            <option value="5+" {{ old('custom_fields.family_size', $customFields['family_size'] ?? '') == '5+' ? 'selected' : '' }}>5+ People</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Property Requirement -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                    </svg>
                                    Property Requirement
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Property Type -->
                                    <div>
                                        <label for="property_type" class="block text-sm font-semibold text-gray-700 mb-2">Property Type</label>
                                        <select name="custom_fields[property_type]" id="property_type"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Select Property Type</option>
                                            <option value="apartment" {{ old('custom_fields.property_type', $customFields['property_type'] ?? '') == 'apartment' ? 'selected' : '' }}>Apartment</option>
                                            <option value="villa" {{ old('custom_fields.property_type', $customFields['property_type'] ?? '') == 'villa' ? 'selected' : '' }}>Villa</option>
                                            <option value="plot" {{ old('custom_fields.property_type', $customFields['property_type'] ?? '') == 'plot' ? 'selected' : '' }}>Plot</option>
                                            <option value="commercial" {{ old('custom_fields.property_type', $customFields['property_type'] ?? '') == 'commercial' ? 'selected' : '' }}>Commercial</option>
                                            <option value="office" {{ old('custom_fields.property_type', $customFields['property_type'] ?? '') == 'office' ? 'selected' : '' }}>Office</option>
                                            <option value="shop" {{ old('custom_fields.property_type', $customFields['property_type'] ?? '') == 'shop' ? 'selected' : '' }}>Shop</option>
                                        </select>
                                    </div>

                                    <!-- Configuration -->
                                    <div>
                                        <label for="configuration" class="block text-sm font-semibold text-gray-700 mb-2">Configuration</label>
                                        <select name="custom_fields[configuration]" id="configuration"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Select Configuration</option>
                                            <option value="studio" {{ old('custom_fields.configuration', $customFields['configuration'] ?? '') == 'studio' ? 'selected' : '' }}>Studio</option>
                                            <option value="1_bhk" {{ old('custom_fields.configuration', $customFields['configuration'] ?? '') == '1_bhk' ? 'selected' : '' }}>1 BHK</option>
                                            <option value="2_bhk" {{ old('custom_fields.configuration', $customFields['configuration'] ?? '') == '2_bhk' ? 'selected' : '' }}>2 BHK</option>
                                            <option value="3_bhk" {{ old('custom_fields.configuration', $customFields['configuration'] ?? '') == '3_bhk' ? 'selected' : '' }}>3 BHK</option>
                                            <option value="4_bhk" {{ old('custom_fields.configuration', $customFields['configuration'] ?? '') == '4_bhk' ? 'selected' : '' }}>4 BHK</option>
                                            <option value="4+_bhk" {{ old('custom_fields.configuration', $customFields['configuration'] ?? '') == '4+_bhk' ? 'selected' : '' }}>4+ BHK</option>
                                        </select>
                                    </div>

                                    <!-- Preferred Location / Area -->
                                    <div>
                                        <label for="preferred_location" class="block text-sm font-semibold text-gray-700 mb-2">Preferred Location / Area</label>
                                        <input type="text" name="custom_fields[preferred_location]" id="preferred_location" 
                                            value="{{ old('custom_fields.preferred_location', $customFields['preferred_location'] ?? '') }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                            placeholder="e.g., Bandra, Andheri, Powai">
                                    </div>

                                    <!-- Project Name -->
                                    <div>
                                        <label for="project_name" class="block text-sm font-semibold text-gray-700 mb-2">Project Name (if any)</label>
                                        <input type="text" name="custom_fields[project_name]" id="project_name" 
                                            value="{{ old('custom_fields.project_name', $customFields['project_name'] ?? '') }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                            placeholder="e.g., Lodha Park, Oberoi Garden">
                                    </div>

                                    @include('leads.partials.inventory-unit-picker', ['inventoryProjects' => $inventoryProjects ?? collect(), 'lead' => $lead])

                                    <!-- Ready-to-Move / Under Construction -->
                                    <div>
                                        <label for="construction_status" class="block text-sm font-semibold text-gray-700 mb-2">Construction Status</label>
                                        <select name="custom_fields[construction_status]" id="construction_status"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Select Status</option>
                                            <option value="ready_to_move" {{ old('custom_fields.construction_status', $customFields['construction_status'] ?? '') == 'ready_to_move' ? 'selected' : '' }}>Ready-to-Move</option>
                                            <option value="under_construction" {{ old('custom_fields.construction_status', $customFields['construction_status'] ?? '') == 'under_construction' ? 'selected' : '' }}>Under Construction</option>
                                            <option value="both" {{ old('custom_fields.construction_status', $customFields['construction_status'] ?? '') == 'both' ? 'selected' : '' }}>Both Acceptable</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Location & Visit -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Location & Visit
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- City -->
                                    <div>
                                        <label for="city" class="block text-sm font-semibold text-gray-700 mb-2">City</label>
                                        <input type="text" name="custom_fields[city]" id="city" 
                                            value="{{ old('custom_fields.city', $customFields['city'] ?? '') }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                            placeholder="e.g., Mumbai, Delhi, Bangalore">
                                    </div>

                                    <!-- Site Visit Required -->
                                    <div>
                                        <label for="site_visit_required" class="block text-sm font-semibold text-gray-700 mb-2">Site Visit Required?</label>
                                        <select name="custom_fields[site_visit_required]" id="site_visit_required" onchange="toggleVisitDate()"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Select Option</option>
                                            <option value="yes" {{ old('custom_fields.site_visit_required', $customFields['site_visit_required'] ?? '') == 'yes' ? 'selected' : '' }}>Yes</option>
                                            <option value="no" {{ old('custom_fields.site_visit_required', $customFields['site_visit_required'] ?? '') == 'no' ? 'selected' : '' }}>No</option>
                                        </select>
                                    </div>

                                    <!-- Preferred Site Visit Date -->
                                    <div id="visit_date_container" class="{{ (old('custom_fields.site_visit_required', $customFields['site_visit_required'] ?? '') == 'yes') ? '' : 'hidden' }}">
                                        <label for="preferred_visit_date" class="block text-sm font-semibold text-gray-700 mb-2">Preferred Site Visit Date</label>
                                        <input type="date" name="custom_fields[preferred_visit_date]" id="preferred_visit_date" 
                                            value="{{ old('custom_fields.preferred_visit_date', $customFields['preferred_visit_date'] ?? '') }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>

                                    <!-- Distance Preference / Nearby Landmarks -->
                                    <div>
                                        <label for="landmarks" class="block text-sm font-semibold text-gray-700 mb-2">Nearby Landmarks / Distance Preference</label>
                                        <input type="text" name="custom_fields[landmarks]" id="landmarks" 
                                            value="{{ old('custom_fields.landmarks', $customFields['landmarks'] ?? '') }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                            placeholder="e.g., Near Metro Station, Airport within 5km">
                                    </div>
                                </div>
                            </div>

                            <!-- Lead Qualification -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    Lead Qualification
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Lead Source -->
                                    <div>
                                        <label for="lead_source" class="block text-sm font-semibold text-gray-700 mb-2">Lead Source</label>
                                        <select name="custom_fields[lead_source]" id="lead_source"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Select Lead Source</option>
                                            <option value="facebook" {{ old('custom_fields.lead_source', $customFields['lead_source'] ?? '') == 'facebook' ? 'selected' : '' }}>Facebook</option>
                                            <option value="google" {{ old('custom_fields.lead_source', $customFields['lead_source'] ?? '') == 'google' ? 'selected' : '' }}>Google</option>
                                            <option value="portal" {{ old('custom_fields.lead_source', $customFields['lead_source'] ?? '') == 'portal' ? 'selected' : '' }}>Portal</option>
                                            <option value="walk_in" {{ old('custom_fields.lead_source', $customFields['lead_source'] ?? '') == 'walk_in' ? 'selected' : '' }}>Walk-in</option>
                                            <option value="referral" {{ old('custom_fields.lead_source', $customFields['lead_source'] ?? '') == 'referral' ? 'selected' : '' }}>Referral</option>
                                            <option value="other" {{ old('custom_fields.lead_source', $customFields['lead_source'] ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>

                                    <!-- Lead Priority -->
                                    <div>
                                        <label for="lead_priority" class="block text-sm font-semibold text-gray-700 mb-2">Lead Priority</label>
                                        <select name="custom_fields[lead_priority]" id="lead_priority"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Select Priority</option>
                                            <option value="hot" {{ old('custom_fields.lead_priority', $customFields['lead_priority'] ?? '') == 'hot' ? 'selected' : '' }}>Hot</option>
                                            <option value="warm" {{ old('custom_fields.lead_priority', $customFields['lead_priority'] ?? '') == 'warm' ? 'selected' : '' }}>Warm</option>
                                            <option value="cold" {{ old('custom_fields.lead_priority', $customFields['lead_priority'] ?? '') == 'cold' ? 'selected' : '' }}>Cold</option>
                                        </select>
                                    </div>

                                    <!-- Buying Timeline -->
                                    <div>
                                        <label for="buying_timeline" class="block text-sm font-semibold text-gray-700 mb-2">Buying Timeline</label>
                                        <select name="custom_fields[buying_timeline]" id="buying_timeline"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Select Timeline</option>
                                            <option value="immediate" {{ old('custom_fields.buying_timeline', $customFields['buying_timeline'] ?? '') == 'immediate' ? 'selected' : '' }}>Immediate</option>
                                            <option value="1_3_months" {{ old('custom_fields.buying_timeline', $customFields['buying_timeline'] ?? '') == '1_3_months' ? 'selected' : '' }}>1-3 Months</option>
                                            <option value="3_6_months" {{ old('custom_fields.buying_timeline', $customFields['buying_timeline'] ?? '') == '3_6_months' ? 'selected' : '' }}>3-6 Months</option>
                                            <option value="6_12_months" {{ old('custom_fields.buying_timeline', $customFields['buying_timeline'] ?? '') == '6_12_months' ? 'selected' : '' }}>6-12 Months</option>
                                            <option value="just_browsing" {{ old('custom_fields.buying_timeline', $customFields['buying_timeline'] ?? '') == 'just_browsing' ? 'selected' : '' }}>Just Browsing</option>
                                        </select>
                                    </div>

                                    <!-- Funding Type -->
                                    <div>
                                        <label for="funding_type" class="block text-sm font-semibold text-gray-700 mb-2">Funding Type</label>
                                        <select name="custom_fields[funding_type]" id="funding_type"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Select Funding Type</option>
                                            <option value="self" {{ old('custom_fields.funding_type', $customFields['funding_type'] ?? '') == 'self' ? 'selected' : '' }}>Self</option>
                                            <option value="loan" {{ old('custom_fields.funding_type', $customFields['funding_type'] ?? '') == 'loan' ? 'selected' : '' }}>Loan</option>
                                            <option value="bank_approved" {{ old('custom_fields.funding_type', $customFields['funding_type'] ?? '') == 'bank_approved' ? 'selected' : '' }}>Bank Approved</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Lead Status
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                                        <select name="status" id="status"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            @foreach(['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'] as $status)
                                                <option value="{{ $status }}" {{ old('status', $lead->status) === $status ? 'selected' : '' }}>
                                                    {{ ucfirst($status) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Note -->
                            <div class="mb-8">
                                <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Add Note
                                </h3>
                                <div>
                                    <label for="note" class="block text-sm font-semibold text-gray-700 mb-2">New Note (Optional)</label>
                                    <textarea name="note" id="note" rows="3"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Add a new note to this lead..."></textarea>
                                </div>
                            </div>

                            <div class="mt-8 flex gap-4">
                                <button type="submit"
                                    class="bg-primary hover:bg-primary/90 text-white font-semibold py-3 px-8 rounded-lg transition">
                                    Update Lead
                                </button>
                                <a href="{{ route('leads.show', $lead) }}"
                                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-8 rounded-lg transition text-center">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleVisitDate() {
            const visitRequired = document.getElementById('site_visit_required').value;
            const visitDateContainer = document.getElementById('visit_date_container');
            
            if (visitRequired === 'yes') {
                visitDateContainer.classList.remove('hidden');
            } else {
                visitDateContainer.classList.add('hidden');
                document.getElementById('preferred_visit_date').value = '';
            }
        }
    </script>
@endsection
