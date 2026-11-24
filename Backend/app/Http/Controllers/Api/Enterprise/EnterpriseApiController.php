<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Controllers\Api\ApiController;
use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\User;
use App\Services\EnterpriseHierarchyService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class EnterpriseApiController extends ApiController
{
    protected function membership(Request $request): EnterpriseMembership
    {
        $membership = $request->attributes->get('enterprise_membership');

        if (!$membership) {
            abort(403, 'Enterprise access only.');
        }

        return $membership;
    }

    protected function enterprise(Request $request): Enterprise
    {
        return $this->membership($request)->enterprise;
    }

    protected function actor(Request $request): User
    {
        return $request->user();
    }

    protected function requireRole(Request $request, array $roles): void
    {
        if (!in_array($this->membership($request)->role, $roles, true)) {
            throw new HttpException(403, 'You do not have permission to perform this action.');
        }
    }

    protected function teamScopeIds(Request $request): array
    {
        $membership = $this->membership($request);

        if ($membership->isAdmin()) {
            return [];
        }

        return $this->hierarchyService()->managedTeamIds($membership);
    }

    /**
     * One hierarchy service instance per request so its membership cache is
     * shared by every scope check in the same call.
     */
    protected function hierarchyService(): EnterpriseHierarchyService
    {
        $request = request();

        if (!$request->attributes->has('enterprise_hierarchy_service')) {
            $request->attributes->set(
                'enterprise_hierarchy_service',
                app(EnterpriseHierarchyService::class)
            );
        }

        return $request->attributes->get('enterprise_hierarchy_service');
    }
}
