<?php
namespace App\Http\Middleware;

use Closure;
use GuzzleHttp\Client;

class GatewayAuth
{
    public function handle($request, Closure $next)
    {
        $mode = env('GATEWAY_MODE', 'introspect');

        if ($mode === 'bypass') {
            // In bypass mode, we'll let the downstream service handle user creation/lookup
            // by not setting X-User-Id, and let TrustGateway middleware handle it
            $request->headers->set('X-User-Email', 'dev@example.com');
            $request->headers->set('X-User-Role', env('GATEWAY_BYPASS_ROLE', 'user'));
            $request->headers->set('X-Bypass-Mode', 'true');
            return $next($request);
        }

        $header = $request->header('Authorization', '');
        if (! preg_match('/^Bearer\s+(.*)$/i', $header, $matches)) {
            return response()->json(['message'=>'Token not provided'], 401);
        }

        $token = $matches[1];
        $client = new Client(['base_uri' => env('AUTH_SERVICE_URL', 'http://127.0.0.1:8001'), 'timeout' => 3.0]);

        try {
            $res = $client->post('/api/introspect', ['json' => ['token' => $token]]);
            $body = json_decode((string)$res->getBody(), true);
            if (! ($body['active'] ?? false)) {
                return response()->json(['message'=>'Invalid token'], 401);
            }
            $request->headers->set('X-User-Id', $body['sub']);
            if (isset($body['email'])) $request->headers->set('X-User-Email', $body['email']);
            if (isset($body['role'])) $request->headers->set('X-User-Role', $body['role']);
            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['message'=>'Introspection failed','error'=>$e->getMessage()], 500);
        }
    }
}


