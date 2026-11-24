<?php

namespace App\Policies;

use App\Models\InventoryFloor;
use App\Models\InventoryUnit;
use App\Models\User;

class InventoryUnitPolicy
{
    public function create(User $user, InventoryFloor $floor): bool
    {
        return $user->role === 'realtor' && $floor->tower->project->user_id === $user->id;
    }

    public function update(User $user, InventoryUnit $unit): bool
    {
        return $user->role === 'realtor' && $unit->floor->tower->project->user_id === $user->id;
    }

    public function delete(User $user, InventoryUnit $unit): bool
    {
        return $user->role === 'realtor' && $unit->floor->tower->project->user_id === $user->id;
    }
}
