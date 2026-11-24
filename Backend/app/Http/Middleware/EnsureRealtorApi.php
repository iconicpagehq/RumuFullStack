<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRealtorApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'realtor') {
            return response()->json([
                'success' => false,
                'message' => 'Realtor access only.',
            ], 403);
        }

        return $next($request);
    }
}
