<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\GatewayController;

class RouteConfig
{
    /**
     * Register all routes from service configurations
     */
    public static function registerAll(): void
    {
        $services = ServiceRegistry::all();

        foreach ($services as $serviceName => $serviceConfig) {
            self::registerServiceRoutes($serviceName, $serviceConfig['routes'] ?? []);
        }

        // Register catch-all dynamic route
        self::registerCatchAllRoute();
    }

    /**
     * Register routes for a specific service
     */
    protected static function registerServiceRoutes(string $serviceName, array $routes): void
    {
        // Group routes by middleware to optimize route registration
        $routesByMiddleware = [];
        
        foreach ($routes as $routeConfig) {
            $path = $routeConfig['path'] ?? '';
            $servicePath = $routeConfig['service_path'] ?? '';
            $methods = $routeConfig['methods'] ?? ['GET'];
            $middleware = $routeConfig['middleware'] ?? [];
            $middlewareKey = md5(serialize($middleware));

            if (empty($path) || empty($servicePath)) {
                continue;
            }

            if (!isset($routesByMiddleware[$middlewareKey])) {
                $routesByMiddleware[$middlewareKey] = [
                    'middleware' => $middleware,
                    'routes' => []
                ];
            }

            $routesByMiddleware[$middlewareKey]['routes'][] = [
                'path' => $path,
                'service_path' => $servicePath,
                'methods' => $methods
            ];
        }

        // Register routes grouped by middleware
        // Note: 'api' prefix is already added by Laravel's RouteServiceProvider
        foreach ($routesByMiddleware as $group) {
            Route::middleware($group['middleware'])
                ->group(function () use ($group, $serviceName) {
                    foreach ($group['routes'] as $routeConfig) {
                        $path = $routeConfig['path'];
                        $servicePath = $routeConfig['service_path'];
                        $methods = $routeConfig['methods'];

                        // Register each HTTP method
                        foreach ($methods as $method) {
                            $routeMethod = strtolower($method);
                            
                            if (in_array($routeMethod, ['get', 'post', 'put', 'patch', 'delete', 'options', 'head'])) {
                                Route::{$routeMethod}($path, function (Request $request, ...$params) use ($serviceName, $servicePath) {
                                    // Replace route parameters in service path
                                    $finalPath = self::replaceRouteParams($servicePath, $params);
                                    return app(GatewayController::class)->route($request, $serviceName, $finalPath);
                                });
                            }
                        }
                    }
                });
        }
    }

    /**
     * Replace route parameters in service path
     */
    protected static function replaceRouteParams(string $path, array $params): string
    {
        $paramIndex = 0;
        return preg_replace_callback('/\{(\w+)\}/', function ($matches) use (&$paramIndex, $params) {
            return $params[$paramIndex++] ?? $matches[0];
        }, $path);
    }

    /**
     * Register catch-all route for dynamic service routing
     */
    protected static function registerCatchAllRoute(): void
    {
        $serviceNames = ServiceRegistry::getServiceNames();
        $servicePattern = implode('|', $serviceNames);

        // Note: 'api' prefix is already added by Laravel's RouteServiceProvider
        Route::any('{service}/{path?}', [GatewayController::class, 'route'])
            ->where('service', $servicePattern)
            ->where('path', '.*');
    }

    /**
     * Get route configuration for a service
     */
    public static function getRoutesForService(string $serviceName): array
    {
        return ServiceRegistry::getRoutes($serviceName);
    }
}

