<?php

use App\Models\User;

test('guests are redirected from the admin dashboard', function () {
    $response = $this->get('/admin');

    $response->assertRedirect(route('login'));
});

test('authenticated users can access the admin dashboard', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get('/admin');

    $response->assertOk()->assertSee('Administration')->assertSee('Vous êtes connecté.');
});

test('the legacy dashboard URL redirects to the admin dashboard', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect(route('admin.dashboard', absolute: false));
});
