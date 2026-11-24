@extends('layouts.admin')

@section('title', 'Create Template - RUMU Admin')
@section('page-title', 'Create Notification Template')
@section('page-subtitle', 'Add a new email template for billing events')

@section('admin-content')
<div class="mb-6">
    <a href="{{ route('admin.billing.templates.index') }}" class="text-gray-600 hover:text-primary text-sm font-medium">
        &larr; Back to Templates
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <form action="{{ route('admin.billing.templates.store') }}" method="POST">
        @csrf
        <div class="p-6 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Template Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Subscription Activated" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Type (Machine Name) <span class="text-red-500">*</span></label>
                    <input type="text" name="type" value="{{ old('type') }}" required placeholder="e.g. subscription_activated" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Must match the snake_case event name exactly.</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Subject <span class="text-red-500">*</span></label>
                <input type="text" name="subject" value="{{ old('subject') }}" required placeholder="e.g. Your RUMU Subscription is now Active!" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Body (HTML supported) <span class="text-red-500">*</span></label>
                <textarea name="body_content" rows="10" required class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">{{ old('body_content') }}</textarea>
                
                <div class="mt-2 bg-gray-50 p-4 rounded border border-gray-200">
                    <h5 class="text-xs font-bold text-gray-700 mb-2 uppercase">Available Placeholders:</h5>
                    <div class="flex flex-wrap gap-2 text-xs font-mono text-primary">
                        <span class="bg-white px-2 py-1 rounded border">@{{customer_name}}</span>
                        <span class="bg-white px-2 py-1 rounded border">@{{customer_email}}</span>
                        <span class="bg-white px-2 py-1 rounded border">@{{plan_name}}</span>
                        <span class="bg-white px-2 py-1 rounded border">@{{subscription_start}}</span>
                        <span class="bg-white px-2 py-1 rounded border">@{{subscription_expiry}}</span>
                        <span class="bg-white px-2 py-1 rounded border">@{{payment_amount}}</span>
                        <span class="bg-white px-2 py-1 rounded border">@{{invoice_number}}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                    Template is Active
                </label>
            </div>
            
        </div>
        
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button type="submit" class="inline-flex justify-center items-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90">
                Save Template
            </button>
        </div>
    </form>
</div>
@endsection
