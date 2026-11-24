@extends('layouts.admin')

@section('title', 'Notification Templates - RUMU Admin')
@section('page-title', 'Notification Templates')
@section('page-subtitle', 'Manage automated billing email templates')

@section('admin-content')
<div class="mb-6 flex justify-between items-center">
    <a href="{{ route('admin.billing.notifications.index') }}" class="text-gray-600 hover:text-primary text-sm font-medium">
        &larr; Back to History
    </a>
    <a href="{{ route('admin.billing.templates.create') }}" class="inline-flex justify-center items-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90">
        Create Template
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($templates as $template)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col h-full relative">
        @if(!$template->is_active)
            <span class="absolute top-4 right-4 bg-gray-100 text-gray-600 text-xs font-bold px-2 py-1 rounded">INACTIVE</span>
        @else
            <span class="absolute top-4 right-4 bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded">ACTIVE</span>
        @endif
        
        <h3 class="text-lg font-bold text-gray-900 mb-1 pr-16">{{ $template->name }}</h3>
        <p class="text-xs font-mono text-gray-500 mb-4">{{ $template->type }}</p>
        
        <div class="text-sm text-gray-600 mb-6 flex-grow">
            <strong>Subject:</strong> {{ $template->subject }}
        </div>
        
        <div class="mt-auto border-t border-gray-100 pt-4 flex justify-end">
            <a href="{{ route('admin.billing.templates.edit', $template) }}" class="text-primary hover:text-primary/80 font-medium text-sm">
                Edit Template &rarr;
            </a>
        </div>
    </div>
    @empty
    <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
        <p class="text-gray-500 mb-4">No notification templates have been created yet.</p>
        <a href="{{ route('admin.billing.templates.create') }}" class="text-primary font-medium hover:underline">Create your first template</a>
    </div>
    @endforelse
</div>
@endsection
