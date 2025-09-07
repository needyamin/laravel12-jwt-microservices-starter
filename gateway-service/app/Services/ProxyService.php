<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxyService
{
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
            'timeout' => 30,
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
            return response($response->body(), $response->status())
                ->withHeaders($response->headers());
        } catch (\Exception $e) {
            Log::error('Proxy forward error: '.$e->getMessage());
            return response()->json(['error' => 'Service temporarily unavailable'], 503);
        }
    }
}


