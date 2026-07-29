<?php

use App\Enums\AdviceRequestStatus;
use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Models\AdviceRequest;

function advicePayload(array $overrides = []): array
{
    return array_merge([
        'customer_name' => 'Camille',
        'customer_email' => 'camille@example.test',
        'environment' => PlantEnvironment::INDOOR->value,
        'exposure' => Exposure::PARTIAL_SHADE->value,
        'space_size' => SpaceSize::MEDIUM->value,
        'maintenance_availability' => Level::LOW->value,
        'has_pets' => '0',
        'free_text_description' => 'Je cherche une plante facile pour mon salon lumineux.',
        'consent' => '1',
    ], $overrides);
}

test('visitors can open the public advice form without authentication', function () {
    $this->get(route('advice.create'))
        ->assertOk()
        ->assertSee('Décrivez votre espace')
        ->assertSee('free_text_description');
});

test('a visitor can submit a valid advice request with a secure public token', function () {
    $response = $this->post(route('advice.store'), advicePayload());

    $response->assertRedirect(route('advice.create'))
        ->assertSessionHas('status');

    $request = AdviceRequest::query()->sole();

    expect($request->status)->toBe(AdviceRequestStatus::PENDING)
        ->and($request->public_token)->toMatch('/^[a-f0-9]{64}$/')
        ->and($request->public_token)->not->toBe((string) $request->id)
        ->and($request->customer_email)->toBe('camille@example.test')
        ->and($request->has_pets)->toBeFalse();
});

test('optional visitor contact details can be omitted', function () {
    $response = $this->post(route('advice.store'), advicePayload([
        'customer_name' => null,
        'customer_email' => null,
        'has_pets' => '1',
    ]));

    $response->assertRedirect(route('advice.create'));

    $request = AdviceRequest::query()->sole();

    expect($request->customer_name)->toBeNull()
        ->and($request->customer_email)->toBeNull()
        ->and($request->has_pets)->toBeTrue();
});

test('advice request validation rejects unsupported choices and missing consent', function () {
    $response = $this->post(route('advice.store'), advicePayload([
        'environment' => PlantEnvironment::BOTH->value,
        'exposure' => 'INVALID',
        'space_size' => null,
        'free_text_description' => 'Trop court',
        'consent' => null,
    ]));

    $response->assertSessionHasErrors([
        'environment',
        'exposure',
        'space_size',
        'free_text_description',
        'consent',
    ]);

    expect(AdviceRequest::query()->count())->toBe(0);
});

test('public advice creation is rate limited', function () {
    foreach (range(1, 10) as $attempt) {
        $this->post(route('advice.store'), advicePayload([
            'customer_email' => "visitor-{$attempt}@example.test",
        ]))->assertRedirect(route('advice.create'));
    }

    $this->post(route('advice.store'), advicePayload([
        'customer_email' => 'blocked@example.test',
    ]))->assertTooManyRequests();
});
