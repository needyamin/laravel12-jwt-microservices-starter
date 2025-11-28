<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $userRole = $request->header('X-User-Role');
        
        if (!$userRole || !in_array($userRole, $roles)) {
            return response()->json([
                'error' => 'Insufficient permissions',
                'message' => 'This action requires one of the following roles: ' . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}
