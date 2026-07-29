<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Plant;
use App\Models\User;

class PlantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, Plant $plant): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Plant $plant): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, Plant $plant): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $user->email_verified_at !== null;
    }
}
