<?php

use App\Models\User;
use Database\Seeders\ManagerSeeder;
use Illuminate\Support\Facades\Hash;

test('manager seeder provisions the configured manager idempotently', function () {
    config()->set('manager.name', 'Gérante de test');
    config()->set('manager.email', 'manager@example.test');
    config()->set('manager.password', 'local-test-password');

    $this->seed(ManagerSeeder::class);
    $this->seed(ManagerSeeder::class);

    $manager = User::query()->where('email', 'manager@example.test')->sole();

    expect(User::query()->count())->toBe(1)
        ->and($manager->name)->toBe('Gérante de test')
        ->and($manager->email_verified_at)->not->toBeNull()
        ->and(Hash::check('local-test-password', $manager->password))->toBeTrue();
});
