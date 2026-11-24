<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::latest()->paginate(20);
        return view('admin.billing.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.billing.coupons.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'applicable_plans' => 'nullable|string', // Comma separated list for now
            'start_date' => 'nullable|date',
            'expiration_date' => 'nullable|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'customer_usage_limit' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['applicable_plans'])) {
            $validated['applicable_plans'] = array_map('trim', explode(',', $validated['applicable_plans']));
        } else {
            $validated['applicable_plans'] = null;
        }

        $validated['code'] = strtoupper($validated['code']);

        Coupon::create($validated);

        return redirect()->route('admin.billing.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.billing.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
            'expiration_date' => 'nullable|date',
            'usage_limit' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $coupon->update($validated);

        return redirect()->route('admin.billing.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.billing.coupons.index')->with('success', 'Coupon deleted successfully.');
    }
}
