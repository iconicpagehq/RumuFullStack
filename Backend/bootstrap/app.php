<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'meta/webhook',
            'webhooks/razorpay',
            'oauth/token',
            'oauth/register',
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'theme' => \App\Http\Middleware\ApplyUserTheme::class,
            'subscription.active' => \App\Http\Middleware\EnsureSubscriptionActive::class,
            'realtor.api' => \App\Http\Middleware\EnsureRealtorApi::class,
            'admin.api' => \App\Http\Middleware\EnsureAdminApi::class,
            'subscription.active.api' => \App\Http\Middleware\EnsureSubscriptionActiveApi::class,
            'enterprise.api' => \App\Http\Middleware\EnsureEnterpriseApi::class,
            'enterprise.role' => \App\Http\Middleware\EnsureEnterpriseRole::class,
        ]);
        
        $middleware->web(append: [
            \App\Http\Middleware\ApplyUserTheme::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Request failed.',
                ], $e->getStatusCode());
            }
        });
    })->create();
