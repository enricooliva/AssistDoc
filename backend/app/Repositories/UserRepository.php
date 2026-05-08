<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findActiveByEmail(string $email): ?User
    {
        return User::query()
            ->with(['tenant', 'roleAssignment'])
            ->where('email', mb_strtolower($email))
            ->where('status', 'active')
            ->first();
    }

    public function findActiveById(int|string $id): ?User
    {
        return User::query()
            ->with(['tenant', 'roleAssignment'])
            ->whereKey($id)
            ->where('status', 'active')
            ->first();
    }
}
