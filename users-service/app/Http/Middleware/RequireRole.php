<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $decoded = $request->attributes->get('user');
        $role = null;
        if (is_object($decoded) && isset($decoded->role)) {
            $role = $decoded->role;
        } else {
            $role = $request->header('X-User-Role');
        }

        if (! $role) {
            return response()->json(['message' => 'Forbidden: missing role'], 403);
        }

        $role = strtolower((string) $role);
        $allowed = array_map(fn($r) => strtolower(trim($r)), $roles);
        if (! in_array($role, $allowed, true)) {
            return response()->json(['message' => 'Forbidden: insufficient role'], 403);
        }

        return $next($request);
    }
}


