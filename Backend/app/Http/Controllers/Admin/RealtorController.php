<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RealtorController extends Controller
{
    /**
     * Display a listing of Realtors.
     */
    public function index()
    {
        $realtors = User::where('role', 'realtor')
            ->with(['plan', 'suspendedPlan', 'campaigns'])
            ->latest()
            ->paginate(10);

        return view('admin.realtors.index', compact('realtors'));
    }

    /**
     * Display the specified realtor.
     */
    public function show(User $realtor)
    {
        if ($realtor->role !== 'realtor') {
            abort(404);
        }

        $realtor->load(['plan', 'suspendedPlan', 'campaigns.leads']);

        return view('admin.realtors.show', compact('realtor'));
    }

    /**
     * Show the form for editing the specified realtor.
     */
    public function edit(User $realtor)
    {
        if ($realtor->role !== 'realtor') {
            abort(404);
        }

        $plans = Plan::ordered()->get();
        return view('admin.realtors.edit', compact('realtor', 'plans'));
    }

    /**
     * Update the specified Realtor in storage.
     */
    public function update(Request $request, User $realtor)
    {
        if ($realtor->role !== 'realtor') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $realtor->id,
            'plan_id' => 'required|exists:plans,id',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $realtor->name = $validated['name'];
        $realtor->email = $validated['email'];
        $realtor->plan_id = $validated['plan_id'];

        if (!empty($validated['password'])) {
            $realtor->password = Hash::make($validated['password']);
        }

        $realtor->save();

        return redirect()->route('admin.realtors.show', $realtor)
            ->with('success', 'Realtor profile updated successfully.');
    }

    /**
     * Remove the specified Realtor from storage.
     */
    public function destroy(User $realtor)
    {
        if ($realtor->role !== 'realtor') {
            abort(404);
        }

        // Optional: Delete related campaigns and leads logic could go here if cascade delete isn't set
        // But soft deletes are enabled, so we just delete the user.

        $realtor->delete();

        return redirect()->route('admin.realtors.index')
            ->with('success', 'Realtor account deleted successfully.');
    }

    public function suspendSubscription(Request $request, User $realtor)
    {
        if ($realtor->role !== 'realtor') {
            abort(404);
        }

        if ($realtor->isSubscriptionSuspended()) {
            return back()->with('error', 'Subscription is already suspended.');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $planName = $realtor->plan?->name ?? 'current plan';
        $realtor->suspendSubscription($validated['reason'] ?? null);

        return back()->with('success', "{$realtor->name}'s subscription ({$planName}) has been suspended.");
    }

    public function resumeSubscription(User $realtor)
    {
        if ($realtor->role !== 'realtor') {
            abort(404);
        }

        if (!$realtor->isSubscriptionSuspended()) {
            return back()->with('error', 'Subscription is not suspended.');
        }

        $restoredPlan = $realtor->suspendedPlan?->name ?? 'previous plan';
        $realtor->resumeSubscription();

        return back()->with('success', "{$realtor->name}'s subscription has been resumed ({$restoredPlan}).");
    }
}
