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

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles = null): Response
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
            
            // Get user from database
            $user = User::find($decoded->sub);
            
            if (!$user || !$user->is_active) {
                return response()->json([
                    'error' => 'User not found or inactive'
                ], 401);
            }
            
            // Add user info to request
            $request->merge(['user' => $user]);
            
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
