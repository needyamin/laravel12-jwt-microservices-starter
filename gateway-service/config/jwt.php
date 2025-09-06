<?php

return [
    'secret' => env('JWT_SECRET', 'your-secret-key-change-this-in-production'),
    'algo' => 'HS256',
    'expire' => 3600, // 1 hour
    'refresh_expire' => 86400, // 24 hours
    
    // JWT Bypass Control
    'bypass_enabled' => env('JWT_BYPASS', false), // Force bypass regardless of environment
    
    // Mock User Configuration (for development)
    'mock_user' => [
        'id' => env('JWT_MOCK_USER_ID', 1),
        'name' => env('JWT_MOCK_USER_NAME', 'Dev User'),
        'email' => env('JWT_MOCK_USER_EMAIL', 'dev@localhost.com'),
        'role' => env('JWT_MOCK_USER_ROLE', 'admin'),
    ],
];
