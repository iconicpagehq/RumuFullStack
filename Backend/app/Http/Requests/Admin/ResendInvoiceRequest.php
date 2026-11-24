<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;

class ResendInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $invoice = $this->route('invoice');
            
            // Rate limit resends to once per minute per invoice to prevent spam
            $key = 'resend-invoice-' . $invoice->id;
            
            if (RateLimiter::tooManyAttempts($key, 1)) {
                $validator->errors()->add('resend', 'Please wait before resending this invoice email.');
            }
        });
    }

    /**
     * Passed validation hook.
     */
    protected function passedValidation()
    {
        $invoice = $this->route('invoice');
        $key = 'resend-invoice-' . $invoice->id;
        
        RateLimiter::hit($key, 60); // Block for 60 seconds
    }
}
