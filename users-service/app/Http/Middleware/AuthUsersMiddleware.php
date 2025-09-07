<?php
namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;

class AuthUsersMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        try {
            $secret = env('USERS_JWT_SECRET', env('JWT_SECRET'));
            if (!$secret) {
                return response()->json(['message' => 'JWT Secret not configured'], 500);
            }

            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            $request->attributes->add(['user' => $decoded]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}


