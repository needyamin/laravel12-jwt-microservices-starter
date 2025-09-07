<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\ProxyService;

class GatewayController extends Controller
{
    private $services = [
        'users' => 'http://localhost:8001',
        'orders' => 'http://localhost:8002',
    ];

    public function __construct(private ProxyService $proxy) {}

    /**
     * Route requests to appropriate microservices
     */
    public function route(Request $request, string $service, string $path = '')
    {
        if (!isset($this->services[$service])) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        $serviceUrl = $this->services[$service];
        Log::info('Gateway routing', ['service' => $service, 'path' => $path]);
        return $this->proxy->forward($request, $serviceUrl, $path);
    }

    /**
     * Health check endpoint
     */
    public function health()
    {
        $services = [];
        
        foreach ($this->services as $name => $url) {
            try {
                $response = Http::timeout(5)->get($url . '/api/health');
                $services[$name] = [
                    'status' => $response->successful() ? 'up' : 'down',
                    'response_time' => $response->transferStats?->getHandlerStat('total_time') ?? 0
                ];
            } catch (\Exception $e) {
                $services[$name] = [
                    'status' => 'down',
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'gateway' => 'up',
            'services' => $services,
            'timestamp' => now()
        ]);
    }
}
