<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnterpriseRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $membership = $request->attributes->get('enterprise_membership');

        if (!$membership || !in_array($membership->role, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
