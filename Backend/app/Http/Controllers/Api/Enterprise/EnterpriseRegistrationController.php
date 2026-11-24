<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\EnterpriseMembershipResource;
use App\Http\Resources\PlanResource;
use App\Http\Resources\UserResource;
use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\EnterpriseAuditService;
use Database\Seeders\EnterprisePlanSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EnterpriseRegistrationController extends ApiController
{
    public function plans()
    {
        if (!Plan::forEnterprise()->exists()) {
            app(EnterprisePlanSeeder::class)->run();
        }

        $plans = Plan::forEnterprise()->active()->ordered()->get();

        return $this->success(PlanResource::collection($plans));
    }

    public function register(Request $request, EnterpriseAuditService $audit)
    {
        $validated = $request->validate([
            'company.name' => 'required|string|max:255',
            'company.legal_name' => 'nullable|string|max:255',
            'company.email' => 'required|email|max:255|unique:enterprises,email',
            'company.phone' => 'nullable|string|max:30',
            'company.address' => 'nullable|string|max:1000',
            'company.website' => 'nullable|url|max:255',
            'company.business_type' => 'nullable|string|max:100',
            'company.company_size' => 'nullable|string|max:50',
            'admin.name' => 'required|string|max:255',
            'admin.email' => 'required|email|max:255|unique:users,email',
            'admin.phone' => 'nullable|string|max:30',
            'admin.password' => 'required|string|min:8|confirmed',
            'plan_id' => 'required|integer|exists:plans,id',
            'billing_interval' => 'nullable|in:monthly,annual',
            'terms_accepted' => 'required|accepted',
        ]);

        $plan = Plan::forEnterprise()->active()->find($validated['plan_id']);

        if (!$plan) {
            throw ValidationException::withMessages([
                'plan_id' => ['Select an active enterprise plan.'],
            ]);
        }

        $result = DB::transaction(function () use ($validated, $plan, $audit) {
            $user = User::create([
                'name' => $validated['admin']['name'],
                'email' => strtolower($validated['admin']['email']),
                'phone' => $validated['admin']['phone'] ?? null,
                'password' => Hash::make($validated['admin']['password']),
                'role' => 'enterprise',
                'plan_id' => $plan->id,
            ]);

            $enterprise = Enterprise::create([
                'name' => $validated['company']['name'],
                'legal_name' => $validated['company']['legal_name'] ?? null,
                'email' => strtolower($validated['company']['email']),
                'phone' => $validated['company']['phone'] ?? null,
                'address' => $validated['company']['address'] ?? null,
                'website' => $validated['company']['website'] ?? null,
                'business_type' => $validated['company']['business_type'] ?? null,
                'company_size' => $validated['company']['company_size'] ?? null,
                'owner_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14),
                'settings' => [
                    'billing_interval' => $validated['billing_interval'] ?? 'monthly',
                    'auto_assign_round_robin' => false,
                    'lead_duplicate_detection' => true,
                ],
            ]);

            EnterpriseMembership::create([
                'enterprise_id' => $enterprise->id,
                'user_id' => $user->id,
                'role' => EnterpriseMembership::ROLE_ADMIN,
                'status' => EnterpriseMembership::STATUS_ACTIVE,
                'designation' => 'Company Administrator',
                'joined_at' => now(),
            ]);

            Subscription::create([
                'user_id' => $user->id,
                'plan_name' => $plan->name . ' (Trial)',
                'duration_months' => 1,
                'start_date' => now(),
                'expiry_date' => now()->addDays(14),
                'status' => 'trial',
                'price' => 0,
                'gst' => 0,
                'discount' => 0,
                'internal_notes' => "Enterprise trial for {$enterprise->name} (enterprise #{$enterprise->id}).",
            ]);

            $audit->log($enterprise, $user, 'enterprise.registered', $enterprise, 'Enterprise account created');

            return [$user, $enterprise];
        });

        [$user, $enterprise] = $result;

        $this->notifyPlatformAdmins($enterprise, $user);
        $this->sendWelcomeEmail($user, $enterprise);

        $token = $user->createToken('enterprise-spa')->plainTextToken;

        $user->load([
            'plan',
            'enterpriseMembership.enterprise.plan',
            'enterpriseMembership.primaryTeamMembership.team',
        ]);

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'enterprise' => new \App\Http\Resources\EnterpriseResource($user->enterpriseMembership?->enterprise),
            'membership' => new EnterpriseMembershipResource($user->enterpriseMembership),
        ], 'Enterprise account created.', 201);
    }

    private function notifyPlatformAdmins(Enterprise $enterprise, User $owner): void
    {
        User::where('role', 'admin')->each(function (User $admin) use ($enterprise, $owner) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'enterprise_registered',
                'title' => 'New Enterprise Registered',
                'message' => "{$enterprise->name} ({$owner->email}) registered on the Enterprise Edition.",
                'related_id' => $enterprise->id,
                'related_type' => 'enterprise',
            ]);
        });
    }

    private function sendWelcomeEmail(User $user, Enterprise $enterprise): void
    {
        try {
            Mail::raw(
                "Hello {$user->name},\n\nYour enterprise workspace for {$enterprise->name} is ready. Sign in to start inviting your team and adding leads.\n\n— Rumu",
                fn ($message) => $message->to($user->email)->subject('Your Rumu enterprise workspace is ready')
            );
        } catch (\Throwable $e) {
            Log::warning('Enterprise welcome email failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
