<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'bio' => $this->bio,
            'role' => $this->role,
            'avatar_url' => $this->avatar_url,
            'avatar_initials' => $this->avatar_initials,
            'email_notifications' => (bool) $this->email_notifications,
            'marketing_emails' => (bool) $this->marketing_emails,
            'public_profile' => (bool) $this->public_profile,
            'theme' => $this->theme,
            'language' => $this->language,
            'timezone' => $this->timezone,
            'google_linked' => !empty($this->google_id),
            'google_avatar' => $this->google_avatar,
            'facebook_linked' => !empty($this->facebook_id),
            'facebook_avatar' => $this->facebook_avatar,
            'referral_code' => $this->referral_code,
            'subscription_suspended' => $this->isSubscriptionSuspended(),
            'subscription_suspended_at' => $this->subscription_suspended_at?->toIso8601String(),
            'subscription_suspend_reason' => $this->subscription_suspend_reason,
            'unread_notifications_count' => $this->when(
                isset($this->unread_notifications_count),
                fn () => (int) $this->unread_notifications_count
            ),
            'plan' => $this->whenLoaded('plan', fn () => new PlanResource($this->plan)),
            'enterprise_membership' => $this->whenLoaded(
                'enterpriseMembership',
                fn () => new EnterpriseMembershipResource($this->enterpriseMembership)
            ),
            'campaigns_count' => $this->whenCounted('campaigns'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
