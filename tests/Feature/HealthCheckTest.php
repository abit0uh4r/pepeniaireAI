<?php

test('health endpoint reports that the application is available', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertExactJson([
            'status' => 'ok',
            'application' => config('app.name'),
        ]);
});
