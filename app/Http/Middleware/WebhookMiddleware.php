<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $allowedPositions = ['CEO', 'CSO', 'Area Manager', 'Store Manager'];

        // Check if the user's position is not in the list of allowed positions
        if (!in_array($user->position, $allowedPositions)) {
            return redirect('/user/dashboard'); // Redirect to the dashboard route
        }
        return $next($request);
    }
}
