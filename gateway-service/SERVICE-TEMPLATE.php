<?php

/**
 * Service Template
 * 
 * Copy this template and add it to config/services.php under 'microservices' array
 * 
 * Replace:
 * - {SERVICE_NAME} with your service name (e.g., 'products', 'payments', 'notifications')
 * - {SERVICE_PORT} with your service port (e.g., 8003, 8004, 8005)
 * - {SERVICE_DESCRIPTION} with a description of your service
 * - Update routes as needed
 */

'{SERVICE_NAME}' => [
    'url' => env('{SERVICE_NAME_UPPER}_SERVICE_URL', 'http://localhost:{SERVICE_PORT}'),
    'health_endpoint' => '/api/health',
    'description' => '{SERVICE_DESCRIPTION}',
    'routes' => [
        // Public routes (no authentication)
        [
            'path' => '{service-name}',
            'service_path' => 'api/{service-name}',
            'methods' => ['GET'],
            'middleware' => [],
        ],
        [
            'path' => '{service-name}/{id}',
            'service_path' => 'api/{service-name}/{id}',
            'methods' => ['GET'],
            'middleware' => [],
        ],
        
        // Authenticated routes (require JWT)
        [
            'path' => '{service-name}',
            'service_path' => 'api/{service-name}',
            'methods' => ['POST'],
            'middleware' => ['gateway.auth'],
        ],
        [
            'path' => '{service-name}/{id}',
            'service_path' => 'api/{service-name}/{id}',
            'methods' => ['PUT', 'DELETE'],
            'middleware' => ['gateway.auth'],
        ],
        
        // Admin routes (require admin role)
        [
            'path' => '{service-name}/admin/all',
            'service_path' => 'api/admin/{service-name}',
            'methods' => ['GET'],
            'middleware' => ['gateway.auth', 'require.role:admin,superadmin'],
        ],
        [
            'path' => '{service-name}/{id}/admin',
            'service_path' => 'api/{service-name}/{id}/admin',
            'methods' => ['PUT', 'DELETE'],
            'middleware' => ['gateway.auth', 'require.role:admin,superadmin'],
        ],
    ],
],

