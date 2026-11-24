@extends('layouts.admin')

@section('title', 'Global Subscriptions - RUMU App Admin')
@section('page-title', 'Subscriptions')
@section('page-subtitle', 'Manage all customer subscriptions')

@section('admin-content')
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-800">All Subscriptions</h3>
            </div>
            
            @if($subscriptions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($subscriptions as $sub)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">
                                                {{ strtoupper(substr($sub->user->name ?? '?', 0, 1)) }}
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900">{{ $sub->user->name ?? 'Deleted User' }}</p>
                                                <p class="text-xs text-gray-500">{{ $sub->user->email ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $sub->plan_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $sub->duration_months }} Months</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $sub->start_date->format('M d, Y') }} - <br>
                                        {{ $sub->expiry_date ? $sub->expiry_date->format('M d, Y') : 'Ongoing' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $sub->status === 'active' ? 'bg-green-100 text-green-800' : ($sub->status === 'expired' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ ucfirst($sub->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right space-x-2">
                                        @if($sub->user)
                                            <a href="{{ route('admin.realtors.billing', $sub->user) }}" class="text-indigo-600 hover:text-indigo-900">Manage</a>
                                            
                                            @if($sub->status === 'active')
                                                <form action="{{ route('admin.billing.subscriptions.extend', $sub) }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="months_to_add" value="1">
                                                    <button type="submit" class="text-blue-600 hover:text-blue-900" title="Extend 1 Month">Extend</button>
                                                </form>
                                                <form action="{{ route('admin.billing.subscriptions.cancel', $sub) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this subscription?');">
                                                    @csrf
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Cancel</button>
                                                </form>
                                            @endif
                                            
                                            @if($sub->status === 'expired' || $sub->status === 'cancelled')
                                                <a href="{{ route('admin.realtors.billing', $sub->user) }}" class="text-green-600 hover:text-green-900">Renew</a>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $subscriptions->links() }}
                </div>
            @else
                <div class="p-8 text-center text-gray-500">
                    No subscriptions found.
                </div>
            @endif
        </div>
    </div>
@endsection
