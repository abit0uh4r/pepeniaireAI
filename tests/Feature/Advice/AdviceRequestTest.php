<?php

use App\Enums\AdviceRequestStatus;
use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Jobs\GeneratePlantAdviceJob;
use App\Models\AdviceRequest;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

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

    $request = AdviceRequest::query()->sole();

    $response->assertRedirect(route('advice.track', ['token' => $request->public_token]));

    Queue::assertPushed(GeneratePlantAdviceJob::class, fn (GeneratePlantAdviceJob $job): bool => $job->adviceRequestId === $request->id);

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

    $response->assertRedirect();

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
        ]))->assertRedirect();
    }

    $this->post(route('advice.store'), advicePayload([
        'customer_email' => 'blocked@example.test',
    ]))->assertTooManyRequests();
});

test('a valid public token opens the tracking page without exposing the numeric id', function () {
    $adviceRequest = AdviceRequest::factory()->create();
    $trackingUrl = route('advice.track', ['token' => $adviceRequest->public_token]);

    expect($trackingUrl)->not->toContain('/'.$adviceRequest->id);

    $this->get($trackingUrl)
        ->assertOk()
        ->assertSee('Suivi de votre demande')
        ->assertSee($adviceRequest->status->label())
        ->assertDontSee('customer_email');
});

test('the public status endpoint exposes only safe status data', function () {
    $adviceRequest = AdviceRequest::factory()->create();

    $this->getJson(route('advice.status', ['token' => $adviceRequest->public_token]))
        ->assertOk()
        ->assertJson([
            'status' => AdviceRequestStatus::PENDING->value,
            'label' => AdviceRequestStatus::PENDING->label(),
            'terminal' => false,
        ])
        ->assertJsonMissingPath('customer_email')
        ->assertJsonMissingPath('free_text_description');
});

test('public status polling is rate limited', function () {
    $adviceRequest = AdviceRequest::factory()->create();
    $statusUrl = route('advice.status', ['token' => $adviceRequest->public_token]);

    foreach (range(1, 60) as $attempt) {
        $this->getJson($statusUrl)->assertOk();
    }

    $this->getJson($statusUrl)->assertTooManyRequests();
});

test('terminal statuses stop polling', function (AdviceRequestStatus $status) {
    $adviceRequest = AdviceRequest::factory()->state(['status' => $status])->create();

    $this->getJson(route('advice.status', ['token' => $adviceRequest->public_token]))
        ->assertOk()
        ->assertJson([
            'status' => $status->value,
            'label' => $status->label(),
            'terminal' => true,
        ]);
})->with([
    AdviceRequestStatus::COMPLETED,
    AdviceRequestStatus::FAILED,
]);

test('unknown and numeric public identifiers are rejected', function (string $token) {
    $this->get(route('advice.track', ['token' => $token]))->assertNotFound();
    $this->getJson(route('advice.status', ['token' => $token]))->assertNotFound();
})->with([
    'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
    '123',
]);
