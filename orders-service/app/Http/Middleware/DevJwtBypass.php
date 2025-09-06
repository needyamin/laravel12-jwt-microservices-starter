<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class DevJwtBypass
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles = null): Response
    {
        // Check if we're in local development mode
        if (config('app.env') === 'local' && config('app.debug') === true) {
            // Create a mock user for development
            $mockUser = new User([
                'id' => 1,
                'name' => 'Dev User',
                'email' => 'dev@localhost.com',
                'role' => 'admin',
                'is_active' => true
            ]);
            
            // Add mock user to request as object
            $request->setUserResolver(function () use ($mockUser) {
                return $mockUser;
            });
            
            // Check roles if specified
            if ($roles) {
                $requiredRoles = explode(',', $roles);
                if (!in_array($mockUser->role, $requiredRoles)) {
                    // For development, we can override role if needed
                    if (in_array('admin', $requiredRoles)) {
                        $mockUser->role = 'admin';
                    } elseif (in_array('moderator', $requiredRoles)) {
                        $mockUser->role = 'moderator';
                    }
                }
            }
            
            return $next($request);
        }

        // In production, use normal JWT validation
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Token not provided'
            ], 401);
        }

        try {
            $key = config('jwt.secret');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            
            // Create a minimal user object from JWT data
            $user = new User([
                'id' => $decoded->sub,
                'name' => $decoded->user->name ?? 'Unknown',
                'email' => $decoded->user->email ?? 'unknown@example.com',
                'role' => $decoded->user->role ?? 'user',
                'is_active' => $decoded->user->is_active ?? true
            ]);
            
            // Add user info to request as object
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            
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
}
