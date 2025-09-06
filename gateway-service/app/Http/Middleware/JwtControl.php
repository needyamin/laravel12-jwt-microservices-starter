<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Symfony\Component\HttpFoundation\Response;

class JwtControl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles = null): Response
    {
        // Check JWT bypass settings
        $jwtBypass = $this->shouldBypassJwt();
        
        if ($jwtBypass) {
            return $this->handleDevelopmentMode($request, $next, $roles);
        }
        
        return $this->handleProductionMode($request, $next, $roles);
    }

    /**
     * Determine if JWT should be bypassed
     */
    private function shouldBypassJwt(): bool
    {
        // Method 1: Environment-based (current system)
        if (config('app.env') === 'local' && config('app.debug') === true) {
            return true;
        }
        
        // Method 2: Explicit bypass setting
        if (config('jwt.bypass_enabled', false)) {
            return true;
        }
        
        // Method 3: Force bypass via environment variable
        if (env('JWT_BYPASS', false)) {
            return true;
        }
        
        return false;
    }

    /**
     * Handle development mode (JWT bypass)
     */
    private function handleDevelopmentMode(Request $request, Closure $next, string $roles = null): Response
    {
        // Get mock user configuration
        $mockUser = $this->getMockUser();
        
        // Add mock user to request
        $request->merge(['user' => $mockUser]);
        
        // Check roles if specified
        if ($roles) {
            $requiredRoles = explode(',', $roles);
            if (!in_array($mockUser->role, $requiredRoles)) {
                // For development, we can override role if needed
                if (in_array('admin', $requiredRoles)) {
                    $mockUser->role = 'admin';
                }
            }
        }
        
        return $next($request);
    }

    /**
     * Handle production mode (Full JWT validation)
     */
    private function handleProductionMode(Request $request, Closure $next, string $roles = null): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Token not provided'
            ], 401);
        }

        try {
            $key = config('jwt.secret');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            
            // Add user info to request
            $request->merge(['user' => $decoded]);
            
            // Check roles if specified
            if ($roles) {
                $userRoles = $decoded->roles ?? [];
                $requiredRoles = explode(',', $roles);
                
                if (!array_intersect($userRoles, $requiredRoles)) {
                    return response()->json([
                        'error' => 'Insufficient permissions'
                    ], 403);
                }
            }
            
        } catch (ExpiredException $e) {
            return response()->json([
                'error' => 'Token has expired'
            ], 401);
        } catch (SignatureInvalidException $e) {
            return response()->json([
                'error' => 'Invalid token signature'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Token could not be decoded'
            ], 401);
        }

        return $next($request);
    }

    /**
     * Get mock user configuration
     */
    private function getMockUser()
    {
        return (object) [
            'id' => config('jwt.mock_user.id', 1),
            'name' => config('jwt.mock_user.name', 'Dev User'),
            'email' => config('jwt.mock_user.email', 'dev@localhost.com'),
            'role' => config('jwt.mock_user.role', 'admin')
        ];
    }
}
