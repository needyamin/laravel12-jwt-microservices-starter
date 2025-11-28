<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrustGateway
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->header('X-User-Id');
        $isBypassMode = $request->header('X-Bypass-Mode') === 'true';
        
        if (!$userId && !$isBypassMode) {
            return response()->json(['message' => 'Missing X-User-Id header - requests must pass through gateway'], 401);
        }

        if ($isBypassMode && !$userId) {
            // In bypass mode, create a mock user object
            $userEmail = $request->header('X-User-Email', 'dev@example.com');
            $userRole = $request->header('X-User-Role', 'user');
            
            $request->setUserResolver(function () use ($userEmail, $userRole) {
                return (object) [
                    'id' => 1, // Mock ID for bypass mode
                    'email' => $userEmail,
                    'role' => $userRole,
                    'name' => 'Bypass User'
                ];
            });
        } else {
            // Normal mode - use the provided user ID
            $userEmail = $request->header('X-User-Email', '');
            $userRole = $request->header('X-User-Role', 'user');
            $userName = $request->header('X-User-Name', 'User');
            
            $request->setUserResolver(function () use ($userId, $userEmail, $userRole, $userName) {
                return (object) [
                    'id' => (int) $userId,
                    'email' => $userEmail,
                    'role' => $userRole,
                    'name' => $userName
                ];
            });
        }
        
        return $next($request);
    }
}


