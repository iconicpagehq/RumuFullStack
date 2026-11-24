@extends('layouts.admin')

@section('title', 'Edit Coupon - RUMU App Admin')
@section('page-title', 'Edit Coupon: ' . $coupon->code)

@section('header-actions')
    <div class="flex space-x-3">
        <a href="{{ route('admin.billing.coupons.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 bg-white border border-gray-300 rounded-md px-4 py-2">
            Cancel
        </a>
        <form action="{{ route('admin.billing.coupons.destroy', $coupon) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this coupon? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700 bg-red-50 border border-red-200 rounded-md px-4 py-2">
                Delete
            </button>
        </form>
    </div>
@endsection

@section('admin-content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="{{ route('admin.billing.coupons.update', $coupon) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="p-6 space-y-6">
                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Coupon Code (Read-only)</label>
                        <input type="text" value="{{ $coupon->code }}" disabled class="shadow-sm block w-full sm:text-sm border-gray-300 rounded-md bg-gray-100 text-gray-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Coupon Name *</label>
                        <input type="text" name="name" value="{{ old('name', $coupon->name) }}" required class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description (Customer facing)</label>
                    <textarea name="description" rows="2" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">{{ old('description', $coupon->description) }}</textarea>
                </div>

                <!-- Status & Config -->
                <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-4">Settings</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select name="is_active" required class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                                <option value="1" {{ old('is_active', $coupon->is_active) ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active', $coupon->is_active) == false ? 'selected' : '' }}>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Expiration Date (optional)</label>
                            <input type="date" name="expiration_date" value="{{ old('expiration_date', $coupon->expiration_date ? $coupon->expiration_date->format('Y-m-d') : '') }}" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total Usage Limit (optional)</label>
                            <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" placeholder="Leave blank for unlimited" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Internal Notes</label>
                            <textarea name="notes" rows="1" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">{{ old('notes', $coupon->notes) }}</textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Usage Stats -->
                <div class="bg-gray-50 rounded-lg p-4 mt-6 border border-gray-200">
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Usage Statistics</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <div class="text-2xl font-bold text-gray-900">{{ $coupon->usages()->count() }}</div>
                            <div class="text-sm text-gray-500">Total Uses</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-600">{{ config('payments.currency') }} {{ number_format($coupon->usages()->sum('discount_amount'), 2) }}</div>
                            <div class="text-sm text-gray-500">Total Discount Given</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-right">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Update Coupon
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
