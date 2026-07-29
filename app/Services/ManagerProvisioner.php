<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use InvalidArgumentException;

final class ManagerProvisioner
{
    public function provision(string $name, string $email, string $password): User
    {
        $name = trim($name);
        $email = trim($email);

        if ($name === '') {
            throw new InvalidArgumentException('The manager name is required.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('The manager email address is invalid.');
        }

        if (mb_strlen($password) < 12) {
            throw new InvalidArgumentException('The manager password must contain at least 12 characters.');
        }

        $manager = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
            ],
        );

        $manager->forceFill(['email_verified_at' => now()])->save();

        return $manager;
    }
}
