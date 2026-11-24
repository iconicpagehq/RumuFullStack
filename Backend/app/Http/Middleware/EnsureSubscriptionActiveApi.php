<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActiveApi
{
    /**
     * Read-only routes allowed when subscription is suspended.
     */
    private const READ_ONLY_PATTERNS = [
        'api/v1/auth/me',
        'api/v1/realtor/dashboard',
        'api/v1/realtor/campaigns',
        'api/v1/realtor/campaigns/*',
        'api/v1/realtor/leads/*',
        'api/v1/realtor/customers',
        'api/v1/realtor/inventory/*',
        'api/v1/realtor/plans',
        'api/v1/realtor/profile',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'realtor' || !$user->isSubscriptionSuspended()) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Your subscription is suspended. Please renew your plan to continue.',
        ], 403);
    }
}
