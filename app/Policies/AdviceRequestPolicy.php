<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdviceRequest;
use App\Models\User;

final class AdviceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->email_verified_at !== null;
    }

    public function view(User $user, AdviceRequest $adviceRequest): bool
    {
        return $user->email_verified_at !== null;
    }
}
