<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxyService
{
    /**
     * Get timeout from config or environment
     */
    protected function getTimeout(): float
    {
        return (float) config('app.gateway_proxy_timeout', env('GATEWAY_PROXY_TIMEOUT', 30));
    }

    /**
     * Get connect timeout from config or environment
     */
    protected function getConnectTimeout(): float
    {
        return (float) config('app.gateway_connect_timeout', env('GATEWAY_CONNECT_TIMEOUT', 5));
    }

    public function forward(Request $request, string $baseUrl, string $path = '')
    {
        $fullPath = $path ? '/' . ltrim($path, '/') : '';
        $url = rtrim($baseUrl, '/') . $fullPath;

        $method = strtolower($request->method());
        $headers = $request->headers->all();
        unset($headers['host']);

        $forwardHeaders = [];
        foreach ($headers as $key => $values) {
            $forwardHeaders[$key] = is_array($values) ? implode(', ', $values) : $values;
        }
        unset($forwardHeaders['content-type']);

        $options = [
            'headers' => $forwardHeaders,
            'timeout' => $this->getTimeout(),
            'connect_timeout' => $this->getConnectTimeout(),
        ];

        if (in_array(strtoupper($request->method()), ['POST','PUT','PATCH'])) {
            $contentType = $request->header('Content-Type', '');
            if (strpos($contentType, 'application/json') !== false) {
                $options['json'] = $request->all();
                $options['headers']['Content-Type'] = 'application/json';
            } else if (strpos($contentType, 'multipart/form-data') !== false) {
                $options['json'] = $request->all();
                $options['headers']['Content-Type'] = 'application/json';
            } else {
                $options['form_params'] = $request->all();
                if ($contentType) {
                    $options['headers']['Content-Type'] = $contentType;
                }
            }
            $options['headers']['Accept'] = 'application/json';
        }

        try {
            $response = Http::withOptions($options)->$method($url);
            
            // Log slow requests in production
            if (config('app.env') === 'production') {
                $responseTime = $response->transferStats?->getHandlerStat('total_time') ?? 0;
                if ($responseTime > 2.0) {
                    Log::warning('Slow proxy request', [
                        'url' => $url,
                        'method' => $method,
                        'response_time' => $responseTime
                    ]);
                }
            }
            
            return response($response->body(), $response->status())
                ->withHeaders($response->headers());
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Proxy connection error', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'error' => 'Service temporarily unavailable',
                'message' => 'Unable to connect to service'
            ], 503);
        } catch (\Exception $e) {
            Log::error('Proxy forward error', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ]);
            return response()->json([
                'error' => 'Service temporarily unavailable',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request'
            ], 503);
        }
    }
}


