@extends('layouts.app')

@section('title', 'Inventory - Rumu App')

@section('content')
<div class="min-h-screen bg-gray-50">
    @if(Auth::user()->role === 'admin')
        @include('admin.partials.sidebar')
    @else
        @include('realtor.partials.sidebar')
    @endif

    <main class="lg:ml-64 min-h-screen">
        @include('inventory.partials.page-header', [
            'title' => 'Flat Inventory',
            'subtitle' => 'Manage towers, floors, and unit availability',
            'actionUrl' => Auth::user()->role === 'realtor' ? route('inventory.projects.create') : null,
            'actionLabel' => 'New Project',
        ])

        <div class="p-4 sm:p-6 lg:p-8">
            @include('realtor.partials.subscription-suspended-banner')
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
            @endif

            @if($projects->isEmpty())
                <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                    <p class="text-gray-600">No inventory projects yet.</p>
                    @if(Auth::user()->role === 'realtor')
                        <a href="{{ route('inventory.projects.create') }}" class="inline-block mt-4 text-primary font-semibold">Create your first project</a>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($projects as $project)
                        <div class="bg-white rounded-xl shadow-lg p-5 border border-gray-100 hover-lift relative">
                            <div class="flex justify-between items-start mb-3 gap-2">
                                <a href="{{ route('inventory.projects.show', $project) }}" class="flex-1 min-w-0">
                                    <h2 class="text-lg font-bold text-gray-800 hover:text-primary truncate">{{ $project->name }}</h2>
                                </a>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <span class="text-xs px-2 py-1 rounded-full {{ $project->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ ucfirst($project->status) }}</span>
                                    @if(Auth::user()->role === 'realtor')
                                    <form method="POST" action="{{ route('inventory.projects.destroy', $project) }}"
                                        onsubmit="return confirm('Delete project {{ $project->name }} and all towers, floors, and units?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 p-1" title="Delete project">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('inventory.projects.show', $project) }}" class="block">
                                @if($project->city || $project->location)
                                    <p class="text-sm text-gray-500 mb-3">{{ collect([$project->location, $project->city])->filter()->join(', ') }}</p>
                                @endif
                                <div class="grid grid-cols-4 gap-2 text-center text-xs">
                                    <div class="bg-green-50 rounded p-2"><div class="font-bold text-green-700">{{ $project->summary['available'] }}</div><div class="text-gray-500">Avail</div></div>
                                    <div class="bg-yellow-50 rounded p-2"><div class="font-bold text-yellow-700">{{ $project->summary['hold'] }}</div><div class="text-gray-500">Hold</div></div>
                                    <div class="bg-blue-50 rounded p-2"><div class="font-bold text-blue-700">{{ $project->summary['booked'] }}</div><div class="text-gray-500">Booked</div></div>
                                    <div class="bg-red-50 rounded p-2"><div class="font-bold text-red-700">{{ $project->summary['sold'] }}</div><div class="text-gray-500">Sold</div></div>
                                </div>
                                <p class="text-xs text-gray-400 mt-3">{{ $project->towers_count }} towers · {{ $project->summary['total'] }} units</p>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </main>
</div>
@endsection
