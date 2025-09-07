<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        // Optional: log received payload for debugging (comment out in production)
            Log::info('Register payload received', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'content_type' => $request->header('Content-Type'),
                'input_method' => $request->isMethod('POST') ? 'POST' : 'OTHER',
            ]);

        // Handle both JSON and form-data requests
        $data = $request->all();
        
        // If request is form-data and body is empty, try to get data from form fields
        if (empty($data) && $request->isMethod('POST')) {
            $data = [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
                'role' => $request->input('role', 'user')
            ];
        }
    
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|string|in:user,moderator,admin,superadmin'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // Laravel 11 automatically hashes due to 'hashed' cast
                'role' => $data['role'] ?? 'user',
                'is_active' => true
            ]);

            $token = $this->generateToken($user);

            return response()->json([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at
                ],
                'token' => $token
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Registration failed'
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        // Handle both JSON and form-data requests
        $data = $request->all();
        
        // If request is form-data and body is empty, try to get data from form fields
        if (empty($data) && $request->isMethod('POST')) {
            $data = [
                'email' => $request->input('email'),
                'password' => $request->input('password')
            ];
        }

        $validator = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $data['email'])->first();

            if (!$user || !Hash::check($data['password'], $user->password)) {
                return response()->json([
                    'error' => 'Invalid credentials'
                ], 401);
            }

            if (!$user->is_active) {
                return response()->json([
                    'error' => 'Account is deactivated'
                ], 401);
            }

            $token = $this->generateToken($user);

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'last_login' => now()
                ],
                'token' => $token
            ]);

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Login failed'
            ], 500);
        }
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Token not provided'
            ], 401);
        }

        try {
            $key = config('jwt.secret');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            
            $user = User::find($decoded->sub);
            
            if (!$user || !$user->is_active) {
                return response()->json([
                    'error' => 'User not found or inactive'
                ], 401);
            }

            $newToken = $this->generateToken($user);

            return response()->json([
                'message' => 'Token refreshed successfully',
                'token' => $newToken
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid token'
            ], 401);
        }
    }

    /**
     * Introspect JWT token (for gateway)
     */
    public function introspect(Request $request)
    {
        $data = $request->all();
        
        if (!isset($data['token'])) {
            return response()->json([
                'active' => false,
                'error' => 'Token not provided'
            ], 400);
        }

        try {
            $key = config('jwt.secret');
            $decoded = JWT::decode($data['token'], new Key($key, 'HS256'));
            
            $user = User::find($decoded->sub);
            
            if (!$user || !$user->is_active) {
                return response()->json([
                    'active' => false,
                    'error' => 'User not found or inactive'
                ]);
            }

            return response()->json([
                'active' => true,
                'sub' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'iat' => $decoded->iat,
                'exp' => $decoded->exp
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'active' => false,
                'error' => 'Invalid token'
            ]);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        // In a stateless JWT system, logout is typically handled client-side
        // by removing the token. We can log the logout event for audit purposes.
        
        $token = $request->bearerToken();
        if ($token) {
            try {
                $key = config('jwt.secret');
                $decoded = JWT::decode($token, new Key($key, 'HS256'));
                
                Log::info('User logout', [
                    'user_id' => $decoded->sub,
                    'timestamp' => now()
                ]);
            } catch (\Exception $e) {
                // Token might be invalid, but we still want to allow logout
            }
        }

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Generate JWT token
     */
    private function generateToken(User $user)
    {
        $key = config('jwt.secret');
        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + config('jwt.expire'),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ],
            'roles' => [$user->role]
        ];

        return JWT::encode($payload, $key, 'HS256');
    }
}
