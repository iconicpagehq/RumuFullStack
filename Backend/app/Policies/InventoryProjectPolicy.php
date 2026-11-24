<?php

namespace App\Policies;

use App\Models\InventoryProject;
use App\Models\User;

class InventoryProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'realtor'], true);
    }

    public function view(User $user, InventoryProject $project): bool
    {
        return $user->role === 'admin' || $project->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'realtor';
    }

    public function update(User $user, InventoryProject $project): bool
    {
        return $user->role === 'realtor' && $project->user_id === $user->id;
    }

    public function delete(User $user, InventoryProject $project): bool
    {
        return $user->role === 'realtor' && $project->user_id === $user->id;
    }
}
