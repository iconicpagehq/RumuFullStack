<?php

namespace App\Policies;

use App\Models\InventoryFloor;
use App\Models\InventoryTower;
use App\Models\User;

class InventoryFloorPolicy
{
    public function view(User $user, InventoryFloor $floor): bool
    {
        return $user->role === 'realtor' && $floor->tower->project->user_id === $user->id;
    }

    public function create(User $user, InventoryTower $tower): bool
    {
        return $user->role === 'realtor' && $tower->project->user_id === $user->id;
    }

    public function update(User $user, InventoryFloor $floor): bool
    {
        return $user->role === 'realtor' && $floor->tower->project->user_id === $user->id;
    }

    public function delete(User $user, InventoryFloor $floor): bool
    {
        return $user->role === 'realtor' && $floor->tower->project->user_id === $user->id;
    }
}
