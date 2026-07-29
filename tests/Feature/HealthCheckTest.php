<?php

test('health endpoint reports that the application is available', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
        ->assertExactJson([
            'status' => 'ok',
            'application' => config('app.name'),
        ]);
});
