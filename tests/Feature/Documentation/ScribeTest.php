<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('scribe documentation is protected by the web session', function (): void {
    $this->get('/docs')->assertRedirect('/login');
});

test('authenticated managers can view scribe documentation', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/docs')
        ->assertOk()
        ->assertSee('Documentation HTTP')
        ->assertSee('health')
        ->assertSee('conseil/suivi/{token}/status');
});

test('scribe only exposes the selected application endpoints', function (): void {
    $this->actingAs(User::factory()->create());

    expect(config('scribe.routes.0.match.prefixes'))->toBe([
        'health',
        'conseil/suivi/*/status',
    ]);

    expect(Route::has('scribe.openapi'))->toBeTrue();
    expect(Route::has('scribe.postman'))->toBeTrue();
});
