<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserTheme
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $theme = auth()->user()->theme ?? 'light';
            
            // Share theme with all views
            view()->share('userTheme', $theme);
            
            // Apply theme to HTML tag
            config(['app.theme' => $theme]);
        }
        
        return $next($request);
    }
}
