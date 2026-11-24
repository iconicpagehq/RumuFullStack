@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Complete your subscription
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            You are subscribing to the <span class="font-bold text-primary">{{ $plan['name'] }}</span> plan.
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            
            <div class="mb-6 pb-6 border-b border-gray-200">
                <div class="flex justify-between text-sm font-medium text-gray-900 mb-2">
                    <span>Plan</span>
                    <span>{{ $plan['name'] }} (Annual)</span>
                </div>
                <div class="flex justify-between text-sm text-gray-500 mb-2">
                    <span>Duration</span>
                    <span>12 Months</span>
                </div>
                <div class="flex justify-between text-lg font-bold text-gray-900 mt-4 pt-4 border-t border-gray-100">
                    <span>Subtotal</span>
                    <span>{{ config('payments.currency') }} {{ number_format($plan['price']) }}</span>
                </div>
                
                @if(isset($coupon))
                <div class="flex justify-between text-sm text-green-600 mt-2">
                    <span>Discount ({{ $coupon->code }})</span>
                    <span>-{{ config('payments.currency') }} {{ number_format($discountAmount) }}</span>
                </div>
                @endif
                
                @if(isset($gstAmount) && $gstAmount > 0)
                <div class="flex justify-between text-sm text-gray-500 mt-2">
                    <span>GST ({{ $gstPercent ?? 18 }}%)</span>
                    <span>{{ config('payments.currency') }} {{ number_format($gstAmount, 2) }}</span>
                </div>
                @endif
                
                <div class="flex justify-between text-xl font-bold text-primary mt-4 pt-4 border-t border-gray-100">
                    <span>Total Amount</span>
                    <span>{{ config('payments.currency') }} {{ number_format($payment->amount) }}</span>
                </div>
            </div>

            <!-- Coupon Form -->
            @if(!isset($coupon))
            <form action="{{ route('checkout.apply-coupon') }}" method="POST" class="mb-6 pb-6 border-b border-gray-200">
                @csrf
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Have a promo code?</label>
                <div class="flex space-x-2">
                    <input type="text" name="code" id="code" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md" placeholder="Enter code">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                        Apply
                    </button>
                </div>
                @if(isset($validationMessage))
                    <p class="mt-2 text-sm text-red-600">{{ $validationMessage }}</p>
                @endif
            </form>
            @else
            <div class="mb-6 pb-6 border-b border-gray-200 flex justify-between items-center">
                <div class="text-sm">
                    <span class="font-medium text-green-600 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Coupon Applied: <span class="font-bold ml-1">{{ $coupon->code }}</span>
                    </span>
                </div>
                <form action="{{ route('checkout.remove-coupon') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 underline">Remove</button>
                </form>
            </div>
            @endif

            <!-- Razorpay Integration Form -->
            <form action="{{ route('checkout.verify') }}" method="POST" id="razorpay-form">
                @csrf
                <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                <input type="hidden" name="razorpay_order_id" value="{{ $payment->gateway_order_id }}">

                <button type="button" id="pay-btn" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition">
                    Pay {{ config('payments.currency') }} {{ number_format($payment->amount) }} Securely
                </button>
            </form>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    var options = {
        "key": "{{ config('payments.gateways.razorpay.key_id') }}", 
        "amount": "{{ $payment->amount * 100 }}", 
        "currency": "{{ config('payments.currency') }}",
        "name": "RUMU CRM",
        "description": "{{ $plan['name'] }} Subscription",
        "image": "https://iconicpages.com/wp-content/uploads/2025/12/rumu-1.png",
        "order_id": "{{ $payment->gateway_order_id }}", 
        "handler": function (response){
            // Submit form for server-side verification
            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
            document.getElementById('razorpay_signature').value = response.razorpay_signature;
            
            // Disable button and show processing state
            var btn = document.getElementById('pay-btn');
            btn.innerHTML = 'Verifying Payment...';
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');

            document.getElementById('razorpay-form').submit();
        },
        "prefill": {
            "name": "{{ $user->name }}",
            "email": "{{ $user->email }}",
        },
        "theme": {
            "color": "#4f46e5"
        }
    };

    var rzp1 = new Razorpay(options);

    rzp1.on('payment.failed', function (response){
        alert("Payment Failed: " + response.error.description);
    });

    document.getElementById('pay-btn').onclick = function(e){
        rzp1.open();
        e.preventDefault();
    }
</script>
@endsection
