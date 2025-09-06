<?php

return [
    'secret' => env('JWT_SECRET', 'your-secret-key-change-this-in-production'),
    'algo' => 'HS256',
    'expire' => 3600, // 1 hour
    'refresh_expire' => 86400, // 24 hours
];
