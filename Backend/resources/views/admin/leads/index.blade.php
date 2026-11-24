@extends('layouts.admin')

@section('title', 'All Leads - Rumu App')

@section('page-title', 'All Leads')
@section('page-subtitle', 'Manage all leads across campaigns')

@section('header-actions')
    <a href="{{ route('campaigns.index') }}"
        class="bg-primary hover:bg-primary/90 text-white px-3 sm:px-4 py-2.5 sm:py-2 rounded-lg transition flex items-center justify-center space-x-2 text-sm sm:text-base touch-manipulation min-h-[44px] sm:min-h-0 whitespace-nowrap">
        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        <span>View Campaigns</span>
    </a>
@endsection

@section('admin-content')
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-4 sm:mb-6">
        <div class="bg-white rounded-xl shadow-md p-3 sm:p-4 lg:p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium">Total Leads</p>
                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mt-1">
                        {{ $leads->total() }}
                    </p>
                </div>
                <div class="bg-blue-100 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-3 sm:p-4 lg:p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium">Active Campaigns</p>
                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mt-1">
                        {{ \App\Models\Campaign::where('status', 'active')->count() }}
                    </p>
                </div>
                <div class="bg-green-100 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-3 sm:p-4 lg:p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium">Pending</p>
                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mt-1">
                        {{ \App\Models\Lead::where('status', 'pending')->count() }}
                    </p>
                </div>
                <div class="bg-yellow-100 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-3 sm:p-4 lg:p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium">Converted</p>
                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mt-1">
                        {{ \App\Models\Lead::where('status', 'converted')->count() }}
                    </p>
                </div>
                <div class="bg-purple-100 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Leads Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-bold text-gray-800">All Leads</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Lead
                        </th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider hidden sm:table-cell">
                            Campaign
                        </th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider hidden md:table-cell">
                            Contact
                        </th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider hidden lg:table-cell">
                            Date
                        </th>
                        <th class="px-4 sm:px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 sm:px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 sm:h-10 sm:w-10 rounded-full bg-primary/10 flex items-center justify-center mr-3">
                                        <span class="text-primary font-semibold text-sm sm:text-base">
                                            {{ strtoupper(substr($lead->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">{{ $lead->name }}</div>
                                        <div class="text-xs text-gray-500 sm:hidden">
                                            {{ $lead->email ?: $lead->phone }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 hidden sm:table-cell">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $lead->campaign->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 hidden md:table-cell">
                                <div class="text-sm text-gray-900">{{ $lead->email ?: '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $lead->phone ?: '-' }}</div>
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                @php
                                    $statusColors = [
                                        'new' => 'bg-blue-100 text-blue-800',
                                        'contacted' => 'bg-indigo-100 text-indigo-800',
                                        'qualified' => 'bg-purple-100 text-purple-800',
                                        'proposal' => 'bg-pink-100 text-pink-800',
                                        'negotiation' => 'bg-orange-100 text-orange-800',
                                        'won' => 'bg-green-100 text-green-800',
                                        'lost' => 'bg-red-100 text-red-800',
                                        'converted' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                    ];
                                    $statusColor = $statusColors[$lead->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                                    {{ ucfirst($lead->status) }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-500 hidden lg:table-cell">
                                {{ $lead->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-center">
                                <a href="{{ route('leads.show', $lead) }}"
                                    class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-primary bg-primary/5 hover:bg-primary/10 rounded-lg transition">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 sm:px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                    </path>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">No leads found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($leads->hasPages())
            <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
@endsection
