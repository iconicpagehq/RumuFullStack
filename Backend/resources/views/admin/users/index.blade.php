@extends('layouts.admin')

@section('title', 'Users Management - Rumu App')

@section('page-title', 'Users Management')
@section('page-subtitle', 'Manage all system users')

@section('admin-content')
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-4 sm:mb-6">
        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-3 sm:p-4 lg:p-6 border-l-4 border-primary">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium">Total Users</p>
                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mt-1">
                        {{ $users->total() }}
                    </p>
                </div>
                <div class="bg-primary/10 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-3 sm:p-4 lg:p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium">Admins</p>
                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mt-1">
                        {{ \App\Models\User::where('role', 'admin')->count() }}
                    </p>
                </div>
                <div class="bg-blue-500/10 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-3 sm:p-4 lg:p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium">Realtors</p>
                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mt-1">
                        {{ \App\Models\User::where('role', 'realtor')->count() }}
                    </p>
                </div>
                <div class="bg-green-500/10 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg sm:rounded-xl shadow-lg p-3 sm:p-4 lg:p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium">New This Month</p>
                    <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mt-1">
                        {{ \App\Models\User::whereMonth('created_at', now()->month)->count() }}
                    </p>
                </div>
                <div class="bg-purple-500/10 p-2 sm:p-2.5 lg:p-3 rounded-lg">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden">
        <div class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 border-b border-gray-200 bg-gradient-to-r from-primary to-primary/80">
            <h2 class="text-base sm:text-lg lg:text-xl font-bold text-white">All Users</h2>
        </div>
        <div class="overflow-x-auto -mx-3 sm:mx-0">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Name</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                            Email</th>
                        <th class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Role</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                            Plan</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                            Joined</th>
                        <th class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4">
                            <div class="flex items-center">
                                <div
                                    class="w-8 h-8 sm:w-10 sm:h-10 bg-primary/10 rounded-full flex items-center justify-center text-primary font-bold text-sm sm:text-base mr-2 sm:mr-3 flex-shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500 sm:hidden truncate mt-0.5">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 hidden sm:table-cell">
                            <div class="text-sm text-gray-500 truncate max-w-xs">{{ $user->email }}</div>
                        </td>
                        <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4">
                            <span
                                class="px-2 sm:px-2.5 lg:px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $user->role === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 text-sm text-gray-500 hidden md:table-cell">
                            {{ $user->plan->name ?? 'N/A' }}
                        </td>
                        <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 text-sm text-gray-500 hidden lg:table-cell">
                            {{ $user->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4">
                            <button class="text-primary hover:text-primary/80 transition text-sm font-medium py-1.5 sm:py-0 px-2 sm:px-0 touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center sm:justify-start sm:w-auto w-full sm:inline-block bg-primary/5 sm:bg-transparent rounded sm:rounded-none">
                                Edit</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-3 sm:px-4 lg:px-6 py-8 text-center text-gray-500 text-sm">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                    </path>
                                </svg>
                                <p class="mt-2">No users found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($users->hasPages())
            <div class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection