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
            // In bypass mode, create or find a mock user
            $userEmail = $request->header('X-User-Email', 'dev@example.com');
            $userRole = $request->header('X-User-Role', 'user');
            
            // Try to find existing user by email, or create a new one
            $user = \App\Models\User::where('email', $userEmail)->first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => 'Bypass User',
                    'email' => $userEmail,
                    'password' => bcrypt('password123'),
                    'role' => $userRole,
                    'is_active' => true,
                ]);
            }
        } else {
            // Get user from database by ID
            $user = \App\Models\User::find($userId);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
        }

        // Set the user in the request so $request->user() works
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}


