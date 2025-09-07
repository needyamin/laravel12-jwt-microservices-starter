<?php
// Microservices smoke test: auth + CRUD through the gateway
// Run: php test.php

const GW = 'http://127.0.0.1:8000';
const USERS = 'http://127.0.0.1:8001';
const ORDERS = 'http://127.0.0.1:8002';

function out($m){ fwrite(STDOUT, $m."\n"); }
function heading($m){ out("\n=== $m ==="); }

function http($method, $url, $headers = [], $body = null){
    $ch = curl_init();
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => array_map(fn($k) => $k.': '.$headers[$k], array_keys($headers)),
    ];
    if ($body !== null) {
        if (is_array($body)) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
            $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json';
        } else {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        $opts[CURLOPT_HTTPHEADER] = array_map(fn($k) => $k.': '.$headers[$k], array_keys($headers));
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['status'=>$status ?: 0, 'body'=>$body, 'json'=>json_decode($body, true), 'err'=>$err];
}

function assertOk($res, $expected, $label){
    if ($res['status'] !== $expected) {
        out("[FAIL] $label expected=$expected got={$res['status']} body={$res['body']}");
        exit(1);
    }
    out("[OK] $label ($expected)");
}

// Detect if gateway runs in bypass mode
$mode = 'introspect';
$dbg = http('GET', GW.'/api/_debug/env');
if ($dbg['status'] === 200 && isset($dbg['json']['GATEWAY_MODE'])) {
    $mode = $dbg['json']['GATEWAY_MODE'];
}
out('Gateway mode: '.$mode);

// Health
heading('Health');
assertOk(http('GET', GW.'/api/health'), 200, 'gateway /api/health');
assertOk(http('GET', USERS.'/up'), 200, 'users-service /up');
assertOk(http('GET', ORDERS.'/up'), 200, 'orders-service /up');

// Register
heading('Register');
$email = 'user'.time().'@example.com';
$reg = http('POST', USERS.'/api/auth/register', ['Accept'=>'application/json'], [
    'name'=>'Smoke User', 'email'=>$email, 'password'=>'password123', 'password_confirmation'=>'password123'
]);
if ($reg['status'] !== 201) {
    $reg = http('POST', USERS.'/api/register', ['Accept'=>'application/json'], [
        'name'=>'Smoke User', 'email'=>$email, 'password'=>'password123', 'password_confirmation'=>'password123'
    ]);
}
assertOk($reg, 201, 'users-service register');
$uid = $reg['json']['user']['id'] ?? null;

// Login
heading('Login');
$login = http('POST', USERS.'/api/auth/login', ['Accept'=>'application/json','Content-Type'=>'application/json'], [
    'email'=>$email, 'password'=>'password123'
]);
if ($login['status'] !== 200) {
    $login = http('POST', USERS.'/api/login', ['Accept'=>'application/json','Content-Type'=>'application/json'], [
        'email'=>$email, 'password'=>'password123'
    ]);
}
assertOk($login, 200, 'users-service login');
$token = $login['json']['token'] ?? null;
if (!$token && strtolower($mode) !== 'bypass') { out('[FAIL] missing token'); exit(1); }

// Gateway headers
$h = [];
if (strtolower($mode) !== 'bypass') { $h['Authorization'] = 'Bearer '.$token; }

// Current user
heading('Current user via gateway');
assertOk(http('GET', GW.'/api/users/profile', $h), 200, 'GET /api/users/profile');

// User profile update
heading('User profile update via gateway');
assertOk(http('PUT', GW.'/api/users/profile', array_merge($h,['Content-Type'=>'application/json']), ['name'=>'Updated Smoke']), 200, 'PUT /api/users/profile');

// Orders CRUD
heading('Orders CRUD via gateway');
$create = http('POST', GW.'/api/orders', array_merge($h,['Content-Type'=>'application/json']), [
    'total_amount'=>99.99,
    'currency'=>'USD',
    'shipping_address'=>[
        'name'=>'Smoke User',
        'street'=>'123 Test St',
        'city'=>'City',
        'state'=>'ST',
        'postal_code'=>'12345',
        'country'=>'US'
    ],
    'notes'=>'smoke test'
]);
assertOk($create, 201, 'POST /api/orders');
$orderId = $create['json']['id'] ?? null;
assertOk(http('GET', GW.'/api/orders', $h), 200, 'GET /api/orders');
assertOk(http('GET', GW.'/api/orders/'.$orderId, $h), 200, 'GET /api/orders/{id}');
assertOk(http('PUT', GW.'/api/orders/'.$orderId, array_merge($h,['Content-Type'=>'application/json']), ['quantity'=>3]), 200, 'PUT /api/orders/{id}');
assertOk(http('DELETE', GW.'/api/orders/'.$orderId, $h), 200, 'DELETE /api/orders/{id}');

// Logout (if implemented)
heading('Logout (optional)');
if ($token) {
    $logout = http('POST', USERS.'/api/auth/logout', ['Authorization'=>'Bearer '.$token]);
    if (!in_array($logout['status'], [200, 404])) {
        $logout = http('POST', USERS.'/api/logout', ['Authorization'=>'Bearer '.$token]);
    }
}

out("\nAll checks passed ✅");


