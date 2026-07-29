<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('the manager command provisions a verified manager account', function () {
    $this->artisan('manager:create')
        ->expectsQuestion('Manager name', 'Command Manager')
        ->expectsQuestion('Manager email', 'command-manager@example.test')
        ->expectsQuestion('Manager password', 'command-manager-password')
        ->expectsQuestion('Confirm manager password', 'command-manager-password')
        ->expectsOutput('Manager provisioned: command-manager@example.test')
        ->assertExitCode(0);

    $manager = User::query()->where('email', 'command-manager@example.test')->sole();

    expect($manager->name)->toBe('Command Manager')
        ->and($manager->email_verified_at)->not->toBeNull()
        ->and(Hash::check('command-manager-password', $manager->password))->toBeTrue();
});

test('the manager command rejects mismatched passwords', function () {
    $this->artisan('manager:create')
        ->expectsQuestion('Manager name', 'Command Manager')
        ->expectsQuestion('Manager email', 'command-manager@example.test')
        ->expectsQuestion('Manager password', 'command-manager-password')
        ->expectsQuestion('Confirm manager password', 'different-password')
        ->expectsOutput('The manager passwords do not match.')
        ->assertExitCode(1);

    expect(User::query()->count())->toBe(0);
});
