<?php
namespace App\Services;

use App\Models\User;

class UserService
{
    public function getCurrentUser(int $userId): User
    {
        return User::findOrFail($userId);
    }

    public function getById(int $id, int $actorId): User
    {
        if ($id !== $actorId) {
            abort(403, 'Forbidden');
        }
        return User::findOrFail($id);
    }

    public function updateUser(int $id, array $data, int $actorId): User
    {
        if ($id !== $actorId) {
            abort(403, 'Forbidden');
        }
        $user = User::findOrFail($id);
        $user->update($data);
        return $user;
    }

    public function deleteUser(int $id, int $actorId): void
    {
        if ($id !== $actorId) {
            abort(403, 'Forbidden');
        }
        $user = User::findOrFail($id);
        $user->delete();
    }
}


