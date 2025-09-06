<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // XSS Protection - Sanitize input data
        $this->sanitizeInput($request);
        
        // Rate limiting headers
        $response = $next($request);
        
        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', "default-src 'self'");
        
        return $response;
    }

    /**
     * Sanitize input data to prevent XSS
     */
    private function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        $sanitized = $this->recursiveSanitize($input);
        
        // Replace the request data with sanitized data
        $request->replace($sanitized);
    }

    /**
     * Recursively sanitize array data
     */
    private function recursiveSanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'recursiveSanitize'], $data);
        }
        
        if (is_string($data)) {
            // Remove potentially dangerous HTML tags and attributes
            $data = strip_tags($data);
            
            // Escape HTML entities
            $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            // Remove null bytes
            $data = str_replace("\0", '', $data);
            
            return $data;
        }
        
        return $data;
    }
}
