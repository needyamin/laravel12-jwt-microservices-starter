<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\ProxyService;
use App\Services\ServiceRegistry;
use App\Services\KafkaService;

class GatewayController extends Controller
{
    public function __construct(
        private ProxyService $proxy,
        private KafkaService $kafka
    ) {}

    /**
     * Route requests to appropriate microservices
     */
    public function route(Request $request, string $service, string $path = '')
    {
        if (!ServiceRegistry::exists($service)) {
            return response()->json([
                'error' => 'Service not found',
                'service' => $service,
                'available_services' => ServiceRegistry::getServiceNames()
            ], 404);
        }

        $serviceUrl = ServiceRegistry::getUrl($service);
        
        if (!$serviceUrl) {
            return response()->json([
                'error' => 'Service URL not configured',
                'service' => $service
            ], 500);
        }

        Log::info('Gateway routing', [
            'service' => $service,
            'path' => $path,
            'method' => $request->method(),
            'url' => $serviceUrl
        ]);

        return $this->proxy->forward($request, $serviceUrl, $path);
    }

    /**
     * Health check endpoint
     * 
     * Uses Kafka heartbeat events if available, falls back to HTTP calls
     */
    public function health()
    {
        $services = [];
        $allServices = ServiceRegistry::all();
        $useKafka = $this->kafka->isAvailable();
        
        foreach ($allServices as $name => $config) {
            $url = $config['url'] ?? null;
            
            if (!$url) {
                $services[$name] = [
                    'status' => 'down',
                    'error' => 'Service URL not configured'
                ];
                continue;
            }

            // Try Kafka heartbeat first (if enabled)
            if ($useKafka) {
                // Check for recent heartbeat in Kafka
                // In a real implementation, you'd query Kafka for recent heartbeats
                // For now, we'll fall through to HTTP check
                // TODO: Implement Kafka heartbeat consumption
            }

            // Fallback to HTTP health check
            try {
                $healthEndpoint = ServiceRegistry::getHealthEndpoint($name);
                $healthUrl = rtrim($url, '/') . '/' . ltrim($healthEndpoint, '/');
                $timeout = $config['timeout'] ?? config('app.gateway_timeout', 5);
                $response = Http::timeout((float) $timeout)->get($healthUrl);
                
                $services[$name] = [
                    'status' => $response->successful() ? 'up' : 'down',
                    'response_time' => $response->transferStats?->getHandlerStat('total_time') ?? 0,
                    'url' => $url,
                    'description' => $config['description'] ?? null,
                    'check_method' => 'http' // Indicates we used HTTP fallback
                ];
            } catch (\Exception $e) {
                Log::warning('Health check failed', [
                    'service' => $name,
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);
                
                $services[$name] = [
                    'status' => 'down',
                    'error' => config('app.debug') ? $e->getMessage() : 'Service unavailable',
                    'url' => $url,
                    'check_method' => 'http'
                ];
            }
        }

        return response()->json([
            'gateway' => 'up',
            'services' => $services,
            'timestamp' => now()->toIso8601String(),
            'total_services' => count($allServices),
            'kafka_enabled' => $useKafka
        ]);
    }
}
