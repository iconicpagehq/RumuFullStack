@extends('layouts.app')

@section('title', 'Billing Notifications - RUMU CRM')

@section('header-title', 'Billing Notifications')
@section('header-subtitle', 'Stay updated on your subscription and payments.')

@section('content')
    <div class="min-h-screen bg-gray-50">
        @include('realtor.partials.sidebar')

        <!-- Main Content -->
        <main class="lg:ml-64 min-h-screen">
            @include('realtor.partials.header')

            <!-- Content Area -->
            <div class="p-4 sm:p-6 lg:p-8">
                <div class="animate-fade-in max-w-5xl mx-auto space-y-8">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <ul class="divide-y divide-gray-200">
            @forelse($notifications as $notification)
                <li class="p-6 {{ !$notification->read_at ? 'bg-indigo-50/30' : '' }} hover:bg-gray-50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    
                    <div class="flex items-start gap-4">
                        <!-- Icon based on type -->
                        <div class="mt-1 flex-shrink-0">
                            @if(Str::contains($notification->type, 'payment_successful') || Str::contains($notification->type, 'activated'))
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                            @elseif(Str::contains($notification->type, 'failed') || Str::contains($notification->type, 'expired'))
                                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            @endif
                        </div>
                        
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-sm font-bold text-gray-900">
                                    {{ str_replace('_', ' ', Str::title($notification->type)) }}
                                </h3>
                                @if(!$notification->read_at)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary text-white">New</span>
                                @endif
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-2">
                                @if($notification->invoice_id)
                                    Invoice <strong>{{ $notification->invoice->invoice_number ?? '' }}</strong> generated.
                                @elseif($notification->subscription_id)
                                    Subscription for plan <strong>{{ $notification->subscription->plan_name ?? '' }}</strong> updated.
                                @else
                                    A billing event occurred on your account.
                                @endif
                            </p>
                            
                            <p class="text-xs text-gray-500">
                                {{ $notification->created_at->diffForHumans() }} ({{ $notification->created_at->format('M d, Y') }})
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        @if($notification->invoice_id && $notification->invoice)
                            <a href="{{ route('realtor.billing.invoice.show', $notification->invoice) }}" class="text-sm font-medium text-primary hover:underline">View Invoice</a>
                        @endif
                        
                        @if(!$notification->read_at)
                            <form action="{{ route('realtor.billing.notifications.read', $notification) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-900 hover:underline">Mark as Read</button>
                            </form>
                        @endif
                    </div>
                </li>
            @empty
                <li class="p-8 text-center text-gray-500">
                    You have no billing notifications.
                </li>
            @endforelse
        </ul>
        
        @if($notifications->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
                </div>
            </div>
        </main>
    </div>
@endsection
