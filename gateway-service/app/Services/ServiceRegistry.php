<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ServiceRegistry
{
    /**
     * Get all registered services
     */
    public static function all(): array
    {
        return Config::get('services.microservices', []);
    }

    /**
     * Get a specific service configuration
     */
    public static function get(string $serviceName): ?array
    {
        $services = self::all();
        return $services[$serviceName] ?? null;
    }

    /**
     * Get service URL
     */
    public static function getUrl(string $serviceName): ?string
    {
        $service = self::get($serviceName);
        return $service['url'] ?? null;
    }

    /**
     * Check if service exists
     */
    public static function exists(string $serviceName): bool
    {
        return self::get($serviceName) !== null;
    }

    /**
     * Get service routes configuration
     */
    public static function getRoutes(string $serviceName): array
    {
        $service = self::get($serviceName);
        return $service['routes'] ?? [];
    }

    /**
     * Get service health check endpoint
     */
    public static function getHealthEndpoint(string $serviceName): string
    {
        $service = self::get($serviceName);
        return $service['health_endpoint'] ?? '/api/health';
    }

    /**
     * Get all service names
     */
    public static function getServiceNames(): array
    {
        return array_keys(self::all());
    }

    /**
     * Register a new service dynamically (runtime)
     */
    public static function register(string $serviceName, array $config): void
    {
        $services = self::all();
        $services[$serviceName] = $config;
        Config::set('services.microservices', $services);
        Log::info("Service registered: {$serviceName}", $config);
    }
}

