<?php

namespace App\Http\Controllers\Api\Social;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\Request;

abstract class BaseSocialController extends ApiController
{
    /**
     * Resolve the strict tenant context (company_id and user_id).
     * Never trusts frontend-supplied company_id.
     */
    protected function getTenantContext(Request $request): array
    {
        $user = $request->user();
        $companyId = null;

        // 1. Check enterprise membership attribute (set by EnterpriseMiddleware)
        $membership = $request->attributes->get('enterprise_membership');
        if ($membership && $membership->enterprise_id) {
            $companyId = (int) $membership->enterprise_id;
        }

        // 2. Check current active enterprise on user
        if (!$companyId && !empty($user->current_enterprise_id)) {
            $companyId = (int) $user->current_enterprise_id;
        }

        // 3. Check active enterprise relationship
        if (!$companyId && method_exists($user, 'activeEnterprise') && $user->activeEnterprise()) {
            $companyId = (int) $user->activeEnterprise()->id;
        }

        // 4. Check activeEnterpriseMembership method
        if (!$companyId && method_exists($user, 'activeEnterpriseMembership')) {
            $activeMembership = $user->activeEnterpriseMembership();
            if ($activeMembership && $activeMembership->enterprise_id) {
                $companyId = (int) $activeMembership->enterprise_id;
            }
        }

        // 5. Fallback to enterpriseMembership Eloquent relationship
        if (!$companyId && $user->enterpriseMembership && $user->enterpriseMembership->enterprise_id) {
            $companyId = (int) $user->enterpriseMembership->enterprise_id;
        }

        return [
            'company_id' => $companyId,
            'user_id' => (int) $user->id,
        ];
    }

    /**
     * Apply multi-tenant scoping to any query.
     */
    protected function scopeTenant($query, Request $request)
    {
        $ctx = $this->getTenantContext($request);
        if ($ctx['company_id']) {
            return $query->where('company_id', $ctx['company_id']);
        }
        return $query->where('user_id', $ctx['user_id'])->whereNull('company_id');
    }
}

