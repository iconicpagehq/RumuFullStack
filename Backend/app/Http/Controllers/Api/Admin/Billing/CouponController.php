<?php

namespace App\Http\Controllers\Api\Admin\Billing;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends ApiController
{
    public function index(Request $request)
    {
        $query = Coupon::withCount('usages')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $this->paginated(CouponResource::collection(
            $query->paginate((int) $request->input('per_page', 20))
        ));
    }

    public function show(Coupon $coupon)
    {
        $coupon->loadCount('usages');

        return $this->success(new CouponResource($coupon));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCoupon($request);
        $validated['applicable_plans'] = $this->normalizePlans($request->input('applicable_plans'));
        $validated['customer_usage_limit'] = $validated['customer_usage_limit'] ?? 1;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $coupon = Coupon::create($validated);

        return $this->success(new CouponResource($coupon), 'Coupon created.', 201);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $this->validateCoupon($request, $coupon);
        $validated['applicable_plans'] = $this->normalizePlans($request->input('applicable_plans'));

        $coupon->update($validated);

        return $this->success(new CouponResource($coupon->fresh()), 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return $this->success(null, 'Coupon deleted.');
    }

    private function validateCoupon(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => strtoupper((string) $request->input('code'))]);

        return $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'applicable_plans' => 'nullable',
            'start_date' => 'nullable|date',
            'expiration_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'customer_usage_limit' => 'nullable|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);
    }

    private function normalizePlans(mixed $plans): ?array
    {
        if (empty($plans)) {
            return null;
        }

        if (is_string($plans)) {
            $plans = array_map('trim', explode(',', $plans));
        }

        return array_values(array_filter((array) $plans));
    }
}
