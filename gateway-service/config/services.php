<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Microservices Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for all microservices in the system.
    | To add a new service, simply add a new entry to the 'microservices' array.
    |
    | Configuration Structure:
    | - name: Service identifier (used in routes)
    | - url: Base URL of the service
    | - health_endpoint: Health check endpoint (default: /api/health)
    | - routes: Route definitions for the service
    |   - path: Gateway path pattern
    |   - service_path: Path on the target service
    |   - methods: Allowed HTTP methods
    |   - middleware: Middleware groups to apply
    |   - roles: Required roles (optional)
    |
    */

    'microservices' => [
        'users' => [
            'url' => env('USERS_SERVICE_URL', env('AUTH_SERVICE_URL', 'http://127.0.0.1:8001')),
            'health_endpoint' => env('USERS_HEALTH_ENDPOINT', '/api/health'),
            'description' => 'User management and authentication service',
            'timeout' => env('USERS_SERVICE_TIMEOUT', 30),
            'routes' => [
                // Authentication routes (public)
                [
                    'path' => 'auth/login',
                    'service_path' => 'api/auth/login',
                    'methods' => ['POST'],
                    'middleware' => [],
                ],
                [
                    'path' => 'auth/register',
                    'service_path' => 'api/auth/register',
                    'methods' => ['POST'],
                    'middleware' => [],
                ],
                [
                    'path' => 'auth/refresh',
                    'service_path' => 'api/auth/refresh',
                    'methods' => ['POST'],
                    'middleware' => [],
                ],
                [
                    'path' => 'auth/logout',
                    'service_path' => 'api/auth/logout',
                    'methods' => ['POST'],
                    'middleware' => [],
                ],
                // User profile routes (authenticated)
                [
                    'path' => 'users/profile',
                    'service_path' => 'api/profile',
                    'methods' => ['GET', 'PUT'],
                    'middleware' => ['gateway.auth'],
                ],
                // Admin routes
                [
                    'path' => 'users',
                    'service_path' => 'api/',
                    'methods' => ['GET'],
                    'middleware' => ['gateway.auth', 'require.role:admin,superadmin'],
                ],
                [
                    'path' => 'users/{id}',
                    'service_path' => 'api/{id}',
                    'methods' => ['GET', 'PUT', 'DELETE'],
                    'middleware' => ['gateway.auth', 'require.role:admin,superadmin'],
                ],
            ],
        ],

        'orders' => [
            'url' => env('ORDERS_SERVICE_URL', 'http://127.0.0.1:8002'),
            'health_endpoint' => env('ORDERS_HEALTH_ENDPOINT', '/api/health'),
            'description' => 'Order management service',
            'timeout' => env('ORDERS_SERVICE_TIMEOUT', 30),
            'routes' => [
                // User order routes (authenticated)
                [
                    'path' => 'orders',
                    'service_path' => 'api/',
                    'methods' => ['GET', 'POST'],
                    'middleware' => ['gateway.auth'],
                ],
                [
                    'path' => 'orders/{id}',
                    'service_path' => 'api/{id}',
                    'methods' => ['GET', 'PUT', 'DELETE'],
                    'middleware' => ['gateway.auth'],
                ],
                // Admin/Moderator routes
                [
                    'path' => 'orders/admin/all',
                    'service_path' => 'api/admin/all',
                    'methods' => ['GET'],
                    'middleware' => ['gateway.auth', 'require.role:moderator,admin,superadmin'],
                ],
                [
                    'path' => 'orders/{id}/status',
                    'service_path' => 'api/{id}/status',
                    'methods' => ['PUT'],
                    'middleware' => ['gateway.auth', 'require.role:moderator,admin,superadmin'],
                ],
            ],
        ],

        'products' => [
            'url' => env('PRODUCTS_SERVICE_URL', 'http://127.0.0.1:8003'),
            'health_endpoint' => env('PRODUCTS_HEALTH_ENDPOINT', '/api/health'),
            'description' => 'Product catalog service',
            'timeout' => env('PRODUCTS_SERVICE_TIMEOUT', 30),
            'routes' => [
                // Public product routes
                [
                    'path' => 'products',
                    'service_path' => 'api/',
                    'methods' => ['GET'],
                    'middleware' => [],
                ],
                [
                    'path' => 'products/{id}',
                    'service_path' => 'api/{id}',
                    'methods' => ['GET'],
                    'middleware' => [],
                ],
                // Protected product management routes
                [
                    'path' => 'products',
                    'service_path' => 'api/',
                    'methods' => ['POST'],
                    'middleware' => ['gateway.auth'],
                ],
                [
                    'path' => 'products/{id}',
                    'service_path' => 'api/{id}',
                    'methods' => ['PUT', 'DELETE'],
                    'middleware' => ['gateway.auth'],
                ],
            ],
        ],

        'carts' => [
            'url' => env('CARTS_SERVICE_URL', 'http://127.0.0.1:8004'),
            'health_endpoint' => env('CARTS_HEALTH_ENDPOINT', '/api/health'),
            'description' => 'Shopping cart service',
            'timeout' => env('CARTS_SERVICE_TIMEOUT', 30),
            'routes' => [
                // Cart routes (authenticated)
                [
                    'path' => 'carts',
                    'service_path' => 'api/',
                    'methods' => ['GET'],
                    'middleware' => ['gateway.auth'],
                ],
                [
                    'path' => 'carts/items',
                    'service_path' => 'api/items',
                    'methods' => ['POST'],
                    'middleware' => ['gateway.auth'],
                ],
                [
                    'path' => 'carts/items/{itemId}',
                    'service_path' => 'api/items/{itemId}',
                    'methods' => ['PUT', 'DELETE'],
                    'middleware' => ['gateway.auth'],
                ],
                [
                    'path' => 'carts',
                    'service_path' => 'api/',
                    'methods' => ['DELETE'],
                    'middleware' => ['gateway.auth'],
                ],
            ],
        ],

        'payments' => [
            'url' => env('PAYMENTS_SERVICE_URL', 'http://127.0.0.1:8005'),
            'health_endpoint' => env('PAYMENTS_HEALTH_ENDPOINT', '/api/health'),
            'description' => 'Payment processing service',
            'timeout' => env('PAYMENTS_SERVICE_TIMEOUT', 30),
            'routes' => [
                // Payment routes (authenticated)
                [
                    'path' => 'payments',
                    'service_path' => 'api/',
                    'methods' => ['GET', 'POST'],
                    'middleware' => ['gateway.auth'],
                ],
                [
                    'path' => 'payments/{id}',
                    'service_path' => 'api/{id}',
                    'methods' => ['GET'],
                    'middleware' => ['gateway.auth'],
                ],
                [
                    'path' => 'payments/{id}/status',
                    'service_path' => 'api/{id}/status',
                    'methods' => ['PUT'],
                    'middleware' => ['gateway.auth'],
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Configuration for third-party services like Mailgun, Postmark, AWS, etc.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
