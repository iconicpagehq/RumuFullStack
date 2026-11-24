@extends('layouts.admin')

@section('title', 'Reports - RUMU Admin')
@section('page-title', 'Reports')
@section('page-subtitle', 'Generate and download business reports')

@section('admin-content')
<div class="max-w-4xl">
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="{{ route('admin.analytics.reports.export') }}" method="POST">
            @csrf
            
            <div class="p-6 space-y-6">
                <!-- Report Type -->
                <div>
                    <h4 class="text-sm font-medium text-gray-900 mb-3">1. Select Report Type</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none">
                            <input type="radio" name="type" value="revenue" class="sr-only" checked>
                            <span class="flex flex-1">
                                <span class="flex flex-col">
                                    <span class="block text-sm font-medium text-gray-900">Revenue Report</span>
                                    <span class="mt-1 flex items-center text-sm text-gray-500">Includes all successful payments and applied coupons.</span>
                                </span>
                            </span>
                            <svg class="h-5 w-5 text-primary invisible" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        </label>
                        
                        <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none">
                            <input type="radio" name="type" value="customers" class="sr-only">
                            <span class="flex flex-1">
                                <span class="flex flex-col">
                                    <span class="block text-sm font-medium text-gray-900">Customer Report</span>
                                    <span class="mt-1 flex items-center text-sm text-gray-500">Detailed list of customers, plans, and lifetime value.</span>
                                </span>
                            </span>
                            <svg class="h-5 w-5 text-primary invisible" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        </label>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">2. Select Date Range</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" name="start_date" value="{{ now()->subDays(30)->format('Y-m-d') }}" required class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" name="end_date" value="{{ now()->format('Y-m-d') }}" required class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <!-- Format -->
                <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">3. Export Format</h4>
                    <select name="format" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        <option value="csv">CSV (Spreadsheet)</option>
                        <!-- Future Phase: <option value="pdf">PDF Document</option> -->
                    </select>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <button type="submit" class="inline-flex justify-center items-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download Report
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    input[type="radio"]:checked + span + svg {
        visibility: visible;
    }
    input[type="radio"]:checked + span {
        color: #4F46E5;
    }
    input[type="radio"]:checked ~ span {
        border-color: #4F46E5;
        background-color: #EEF2FF;
    }
</style>
<script>
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('label').forEach(label => {
                label.classList.remove('border-primary', 'bg-indigo-50');
            });
            if (this.checked) {
                this.closest('label').classList.add('border-primary', 'bg-indigo-50');
            }
        });
    });
</script>
@endsection
