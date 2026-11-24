<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan_name' => ['required', 'string'],
            'duration_months' => ['required', 'integer', 'min:0'],
            'start_date' => ['required', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'price' => ['required', 'numeric', 'min:0'],
            'gst' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_method' => ['required', 'string'],
            'payment_status' => ['required', 'string', 'in:pending,completed,failed'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'subscription_status' => ['required', 'string', 'in:active,pending,expired,cancelled'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->subscription_status === 'active') {
                $service = app(\App\Services\SubscriptionService::class);
                $realtor = $this->route('realtor');
                
                if ($service->hasOverlap($realtor, $this->start_date, $this->expiry_date)) {
                    $validator->errors()->add('start_date', 'This subscription overlaps with an existing active subscription.');
                }
            }
        });
    }
}
