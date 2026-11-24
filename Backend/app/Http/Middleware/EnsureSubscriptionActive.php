<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_ROUTES = [
        'dashboard',
        'logout',
        'realtor.dashboard',
        'realtor.plans',
        'realtor.plans.upgrade',
        'realtor.profile',
        'realtor.profile.update',
        'realtor.profile.password',
        'realtor.settings',
        'realtor.settings.notifications',
        'realtor.settings.privacy',
        'realtor.settings.appearance',
        'realtor.settings.account.delete',
        'campaigns.index',
        'campaigns.show',
        'leads.show',
        'inventory.projects.index',
        'inventory.projects.show',
        'inventory.projects.create',
        'inventory.projects.edit',
        'inventory.units.search',
        'connect.google.redirect',
        'connect.google.callback',
        'connect.facebook.redirect',
        'connect.facebook.callback',
        'image.avatar',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'realtor' || !$user->isSubscriptionSuspended()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && in_array($routeName, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            return $next($request);
        }

        return redirect()
            ->back()
            ->with('error', 'Your subscription is suspended. Please contact admin or renew your plan to continue.');
    }
}
