<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends ApiController
{
    public function index(Request $request)
    {
        $query = User::with('plan')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        return $this->paginated(UserResource::collection(
            $query->paginate((int) $request->input('per_page', 20))
        ));
    }

    public function show(User $user)
    {
        $user->load('plan')->loadCount('campaigns');

        return $this->success(new UserResource($user));
    }
}
