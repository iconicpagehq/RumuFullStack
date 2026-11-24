@extends('layouts.app')

@section('title', 'Customers - Rumu App')

@section('header-title', 'Customers')
@section('header-subtitle', 'Won leads and booked or sold units')

@section('content')
    <div class="min-h-screen bg-gray-50">
        @include('realtor.partials.sidebar')

        <main class="lg:ml-64 min-h-screen">
            @include('realtor.partials.header')

            <div class="p-4 sm:p-6 lg:p-8">
                @include('realtor.partials.subscription-suspended-banner')

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-primary">
                        <p class="text-gray-500 text-xs font-medium">Total</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-green-500">
                        <p class="text-gray-500 text-xs font-medium">Won</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['won'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-blue-500">
                        <p class="text-gray-500 text-xs font-medium">Booked</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['booked'] }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-red-500">
                        <p class="text-gray-500 text-xs font-medium">Sold</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['sold'] }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <h2 class="text-lg font-bold text-gray-800">All Customers</h2>
                        <form method="GET" class="flex flex-wrap gap-2 items-center">
                            <input type="text" name="search" value="{{ $search }}"
                                placeholder="Search name, email, phone"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-full sm:w-48">
                            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">All statuses</option>
                                <option value="won" {{ $statusFilter === 'won' ? 'selected' : '' }}>Won</option>
                                <option value="booked" {{ $statusFilter === 'booked' ? 'selected' : '' }}>Booked unit</option>
                                <option value="sold" {{ $statusFilter === 'sold' ? 'selected' : '' }}>Sold unit</option>
                            </select>
                            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-semibold">Filter</button>
                            @if($search || $statusFilter)
                                <a href="{{ route('realtor.customers') }}" class="text-sm text-gray-500 hover:text-primary px-2">Clear</a>
                            @endif
                        </form>
                    </div>

                    @if($customers->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500 hidden sm:table-cell">Phone</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500 hidden md:table-cell">Campaign</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Lead</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500 hidden lg:table-cell">Unit</th>
                                        <th class="px-4 py-3 text-left font-medium text-gray-500">Unit status</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($customers as $lead)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ $lead->name }}</td>
                                            <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">{{ $lead->phone ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-500 hidden md:table-cell">{{ $lead->campaign?->name ?? '—' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $lead->status_color }}-100 text-{{ $lead->status_color }}-800">
                                                    {{ ucfirst($lead->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-500 hidden lg:table-cell text-xs">
                                                {{ $lead->inventoryUnit?->fullPath() ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($lead->inventoryUnit)
                                                    @include('inventory.partials.availability-badge', ['status' => $lead->inventoryUnit->availability_status])
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <a href="{{ route('leads.show', $lead) }}" class="text-primary font-semibold hover:underline">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="px-4 py-3 border-t border-gray-100">
                            {{ $customers->links() }}
                        </div>
                    @else
                        <div class="p-12 text-center">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <h3 class="text-lg font-bold text-gray-800 mb-2">No customers yet</h3>
                            <p class="text-gray-600 text-sm mb-4">Customers appear when a lead is won or linked to a booked or sold unit.</p>
                            <a href="{{ route('campaigns.index') }}" class="inline-block bg-primary text-white font-semibold py-2 px-6 rounded-lg text-sm">Go to Campaigns</a>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </div>
@endsection
