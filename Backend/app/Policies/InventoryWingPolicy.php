<?php

namespace App\Policies;

use App\Models\InventoryTower;
use App\Models\InventoryWing;
use App\Models\User;

class InventoryWingPolicy
{
    public function create(User $user, InventoryTower $tower): bool
    {
        return $user->role === 'realtor' && $tower->project->user_id === $user->id;
    }

    public function update(User $user, InventoryWing $wing): bool
    {
        return $user->role === 'realtor' && $wing->tower->project->user_id === $user->id;
    }

    public function delete(User $user, InventoryWing $wing): bool
    {
        return $user->role === 'realtor' && $wing->tower->project->user_id === $user->id;
    }
}
