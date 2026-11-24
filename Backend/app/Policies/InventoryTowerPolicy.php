<?php

namespace App\Policies;

use App\Models\InventoryProject;
use App\Models\InventoryTower;
use App\Models\User;

class InventoryTowerPolicy
{
    public function create(User $user, InventoryProject $project): bool
    {
        return $user->role === 'realtor' && $project->user_id === $user->id;
    }

    public function update(User $user, InventoryTower $tower): bool
    {
        return $user->role === 'realtor' && $tower->project->user_id === $user->id;
    }

    public function delete(User $user, InventoryTower $tower): bool
    {
        return $user->role === 'realtor' && $tower->project->user_id === $user->id;
    }
}
