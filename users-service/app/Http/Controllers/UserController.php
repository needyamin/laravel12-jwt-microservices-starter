<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ]
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
            'current_password' => 'required_with:password|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            // Verify current password if changing password
            if ($request->has('password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'error' => 'Current password is incorrect'
                    ], 422);
                }
            }

            $updateData = [];
            
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }
            
            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'updated_at' => $user->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Profile update failed'
            ], 500);
        }
    }

    /**
     * Get all users (Admin only)
     */
    public function index(Request $request)
    {
        try {
            $users = User::select('id', 'name', 'email', 'role', 'is_active', 'created_at', 'updated_at')
                ->paginate(15);

            return response()->json([
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Users list error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve users'
            ], 500);
        }
    }

    /**
     * Get specific user (Admin only)
     */
    public function show(Request $request, $id)
    {
        try {
            $user = User::select('id', 'name', 'email', 'role', 'is_active', 'created_at', 'updated_at')
                ->find($id);

            if (!$user) {
                return response()->json([
                    'error' => 'User not found'
                ], 404);
            }

            return response()->json([
                'user' => $user
            ]);

        } catch (\Exception $e) {
            Log::error('User show error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to retrieve user'
            ], 500);
        }
    }

    /**
     * Update user (Admin only)
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'role' => 'sometimes|string|in:user,moderator,admin,superadmin',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'error' => 'User not found'
                ], 404);
            }

            $updateData = [];
            
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }
            
            if ($request->has('role')) {
                $updateData['role'] = $request->role;
            }
            
            if ($request->has('is_active')) {
                $updateData['is_active'] = $request->is_active;
            }

            $user->update($updateData);

            return response()->json([
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'updated_at' => $user->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('User update error: ' . $e->getMessage());
            return response()->json([
                'error' => 'User update failed'
            ], 500);
        }
    }

    /**
     * Delete user (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'error' => 'User not found'
                ], 404);
            }

            // Prevent admin from deleting themselves
            if ($user->id === $request->user()->id) {
                return response()->json([
                    'error' => 'Cannot delete your own account'
                ], 422);
            }

            $user->delete();

            return response()->json([
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('User delete error: ' . $e->getMessage());
            return response()->json([
                'error' => 'User deletion failed'
            ], 500);
        }
    }
}
