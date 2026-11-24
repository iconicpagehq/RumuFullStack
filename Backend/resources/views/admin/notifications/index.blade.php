@extends('layouts.admin')

@section('title', 'Notifications - Rumu App')

@section('page-title', 'Notifications')
@section('page-subtitle', 'View and manage your notifications')

@section('admin-content')
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-base sm:text-lg font-bold text-gray-800">All Notifications</h2>
            @if(auth()->user()->unreadNotificationsCount() > 0)
                <form action="{{ route('admin.notifications.mark-all-read') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs sm:text-sm text-primary hover:text-primary/80 font-medium">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>
        
        <div class="divide-y divide-gray-100">
            @forelse($notifications as $notification)
                <div class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4 hover:bg-gray-50 transition {{ $notification->is_read ? '' : 'bg-blue-50/30' }}">
                    <div class="flex items-start gap-2 sm:gap-3 lg:gap-4">
                        <div class="flex-shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-full {{ $notification->is_read ? 'bg-gray-100' : 'bg-primary/10' }} flex items-center justify-center">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 {{ $notification->is_read ? 'text-gray-500' : 'text-primary' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm font-semibold text-gray-800">{{ $notification->title }}</p>
                                    <p class="text-xs text-gray-600 mt-0.5">{{ $notification->message }}</p>
                                    <p class="text-[10px] sm:text-xs text-gray-400 mt-1">{{ $notification->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if($notification->related_type === 'user' && $notification->related_id)
                                        <a href="{{ route('admin.users.index') }}" class="text-xs text-primary hover:text-primary/80 font-medium whitespace-nowrap">
                                            View User
                                        </a>
                                    @endif
                                    @if(!$notification->is_read)
                                        <form action="{{ route('admin.notifications.mark-read', $notification) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs bg-primary text-white px-2 sm:px-3 py-1 rounded-full hover:bg-primary/90 whitespace-nowrap">
                                                Mark Read
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400 whitespace-nowrap">Read</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 sm:py-12 text-center text-gray-500">
                    <svg class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 sm:mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <p class="text-base sm:text-lg font-medium">No notifications yet</p>
                    <p class="text-xs sm:text-sm mt-1">You'll see notifications here when new Realtors register</p>
                </div>
            @endforelse
        </div>
        
        @if($notifications->hasPages())
            <div class="px-3 sm:px-4 lg:px-6 py-3 border-t border-gray-200">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
@endsection
