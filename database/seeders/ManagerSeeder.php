<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ManagerSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('manager.name');
        $email = config('manager.email');
        $password = config('manager.password');

        if (! is_string($name) || $name === '') {
            throw new RuntimeException('MANAGER_NAME must be configured before running the manager seeder.');
        }

        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('MANAGER_EMAIL must contain a valid email address.');
        }

        if (! is_string($password) || mb_strlen($password) < 12) {
            throw new RuntimeException('MANAGER_PASSWORD must contain at least 12 characters.');
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );
    }
}
