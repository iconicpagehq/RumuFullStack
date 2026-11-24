@extends('layouts.admin')

@section('title', 'Manage Realtors - Rumu App')

@section('page-title', 'Manage Realtors')
@section('page-subtitle', 'Monitor and manage all Realtor accounts')

@section('admin-content')
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-2.5 sm:py-3 rounded-lg mb-4 sm:mb-6 animate-fade-in text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg sm:rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto -mx-3 sm:mx-0">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Realtor</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                            Plan</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                            Campaigns</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                            Joined</th>
                        <th
                            class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($realtors as $realtor)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8 sm:h-10 sm:w-10">
                                    <div
                                        class="h-8 w-8 sm:h-10 sm:w-10 rounded-full bg-secondary text-primary flex items-center justify-center font-bold text-sm sm:text-base lg:text-lg">
                                        {{ strtoupper(substr($realtor->name, 0, 1)) }}
                                    </div>
                                </div>
                                <div class="ml-2 sm:ml-3 lg:ml-4 min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $realtor->name }}</div>
                                    @if($realtor->isSubscriptionSuspended())
                                        <span class="inline-flex mt-1 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-red-100 text-red-700">Suspended</span>
                                    @endif
                                    <div class="text-xs sm:text-sm text-gray-500 truncate sm:hidden">{{ $realtor->email }}</div>
                                    <div class="text-xs text-gray-500 hidden sm:block">{{ $realtor->email }}</div>
                                    <div class="flex items-center gap-2 mt-1 sm:hidden">
                                        <span
                                            class="px-2 py-0.5 inline-flex text-xs leading-4 font-semibold rounded-full {{ $realtor->plan->name == 'Pro' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $realtor->plan->name }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ $realtor->campaigns->count() }} Campaigns</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 hidden sm:table-cell">
                            <span
                                class="px-2 sm:px-2.5 lg:px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $realtor->plan->name == 'Pro' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $realtor->plan->name }}
                            </span>
                        </td>
                        <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 text-sm text-gray-500 hidden md:table-cell">
                            {{ $realtor->campaigns->count() }}
                        </td>
                        <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4 text-sm text-gray-500 hidden lg:table-cell">
                            {{ $realtor->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-3 sm:px-4 lg:px-6 py-2.5 sm:py-3 lg:py-4">
                            <div class="flex flex-col sm:flex-row gap-1.5 sm:gap-2">
                                <a href="{{ route('admin.realtors.show', $realtor) }}"
                                    class="text-primary hover:text-primary/70 text-sm font-medium py-1.5 sm:py-0 px-2 sm:px-0 touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center sm:justify-start sm:w-auto w-full sm:inline-block bg-primary/5 sm:bg-transparent rounded sm:rounded-none">
                                    View</a>
                                <a href="{{ route('admin.realtors.edit', $realtor) }}"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium py-1.5 sm:py-0 px-2 sm:px-0 touch-manipulation min-h-[44px] sm:min-h-0 flex items-center justify-center sm:justify-start sm:w-auto w-full sm:inline-block bg-indigo-50 sm:bg-transparent rounded sm:rounded-none">
                                    Edit</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                            </tbody>
                        </table>
                    </div>
        <div class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4 border-t border-gray-200">
            {{ $realtors->links() }}
        </div>
    </div>
@endsection