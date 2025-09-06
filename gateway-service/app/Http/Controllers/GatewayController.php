<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GatewayController extends Controller
{
    private $services = [
        'users' => 'http://localhost:8001',
        'orders' => 'http://localhost:8002',
    ];

    /**
     * Route requests to appropriate microservices
     */
    public function route(Request $request, string $service, string $path = '')
    {
        if (!isset($this->services[$service])) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        $serviceUrl = $this->services[$service];
        $fullPath = $path ? "/{$path}" : '';
        $url = $serviceUrl . $fullPath;

        // Forward the request to the appropriate service
        $method = $request->method();
        $headers = $request->headers->all();
        
        // Remove host header to avoid conflicts
        unset($headers['host']);
        
        // Prepare headers for forwarding
        $forwardHeaders = [];
        foreach ($headers as $key => $values) {
            $forwardHeaders[$key] = is_array($values) ? implode(', ', $values) : $values;
        }
        
        // Remove Content-Type from forwarded headers - we'll set it explicitly
        unset($forwardHeaders['content-type']);
        
        $options = [
            'headers' => $forwardHeaders,
            'timeout' => 30,
        ];

        // Add body for POST/PUT/PATCH requests
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $contentType = $request->header('Content-Type', '');
            
            // Debug logging
            Log::info('Gateway forwarding request', [
                'content_type' => $contentType,
                'request_all' => $request->all(),
                'request_input' => $request->input(),
                'raw_content' => $request->getContent()
            ]);
            
            if (strpos($contentType, 'application/json') !== false) {
                // Handle JSON requests
                $options['json'] = $request->all();
                $options['headers']['Content-Type'] = 'application/json';
            } else {
                // Handle form-data and other formats
                if (strpos($contentType, 'multipart/form-data') !== false) {
                    // For multipart/form-data, convert to JSON for reliable forwarding
                    $options['json'] = $request->all();
                    $options['headers']['Content-Type'] = 'application/json';
                } else {
                    // For other formats, use form_params
                    $options['form_params'] = $request->all();
                    if ($contentType) {
                        $options['headers']['Content-Type'] = $contentType;
                    }
                }
            }
            
            $options['headers']['Accept'] = 'application/json';
        }

        try {
            $response = Http::withOptions($options)->$method($url);
            
            return response($response->body(), $response->status())
                ->withHeaders($response->headers());
                
        } catch (\Exception $e) {
            Log::error('Gateway routing error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Service temporarily unavailable'
            ], 503);
        }
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
