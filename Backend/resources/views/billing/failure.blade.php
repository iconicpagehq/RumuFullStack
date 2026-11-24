@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-12 px-4 shadow sm:rounded-lg sm:px-10 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Payment Failed</h2>
            <p class="text-gray-600 mb-8">
                We couldn't verify your payment. Your card may have been declined, or there was a network error during verification.
            </p>

            <a href="{{ route('checkout.show') }}" class="inline-flex justify-center py-3 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition">
                Try Again
            </a>
        </div>
    </div>
</div>
@endsection
