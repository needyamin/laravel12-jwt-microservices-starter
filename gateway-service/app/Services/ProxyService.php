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

        // Build HTTP request
        $httpRequest = Http::withOptions([
            'timeout' => $this->getTimeout(),
            'connect_timeout' => $this->getConnectTimeout(),
        ])->withHeaders($forwardHeaders);

        if (in_array(strtoupper($request->method()), ['POST','PUT','PATCH'])) {
            $contentType = $request->header('Content-Type', '');
            
            if (strpos($contentType, 'multipart/form-data') !== false) {
                // Handle multipart/form-data (file uploads)
                // First, attach all files
                foreach ($request->allFiles() as $key => $file) {
                    if (is_array($file)) {
                        // Handle multiple files with same key
                        foreach ($file as $f) {
                            $httpRequest = $httpRequest->attach(
                                $key . '[]',
                                file_get_contents($f->getRealPath()),
                                $f->getClientOriginalName()
                            );
                        }
                    } else {
                        $httpRequest = $httpRequest->attach(
                            $key,
                            file_get_contents($file->getRealPath()),
                            $file->getClientOriginalName()
                        );
                    }
                }
                // Then attach all non-file form fields
                foreach ($request->except(array_keys($request->allFiles())) as $key => $value) {
                    if (!is_null($value) && $value !== '') {
                        $httpRequest = $httpRequest->attach(
                            $key,
                            is_array($value) ? json_encode($value) : (string) $value
                        );
                    }
                }
            } else if (strpos($contentType, 'application/json') !== false) {
                $httpRequest = $httpRequest->withHeaders(['Content-Type' => 'application/json'])
                    ->withBody($request->getContent(), 'application/json');
            } else {
                $httpRequest = $httpRequest->asForm();
            }
            
            $httpRequest = $httpRequest->withHeaders(['Accept' => 'application/json']);
        }

        try {
            $response = $httpRequest->$method($url);
            
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


