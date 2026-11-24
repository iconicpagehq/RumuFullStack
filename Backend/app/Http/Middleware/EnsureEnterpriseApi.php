<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnterpriseApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'enterprise') {
            return response()->json([
                'success' => false,
                'message' => 'Enterprise access only.',
            ], 403);
        }

        $membership = $user->enterpriseMembership()
            ->with(['enterprise', 'primaryTeamMembership.team'])
            ->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'No enterprise workspace is linked to this account.',
            ], 403);
        }

        if ($membership->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your enterprise membership is suspended.',
            ], 403);
        }

        if (!$membership->enterprise || !$membership->enterprise->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This enterprise workspace is unavailable.',
            ], 403);
        }

        $request->attributes->set('enterprise_membership', $membership);
        $request->attributes->set('enterprise', $membership->enterprise);

        return $next($request);
    }
}
