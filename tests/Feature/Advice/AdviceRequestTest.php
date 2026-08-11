<?php

use App\Enums\AdviceRequestStatus;
use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Jobs\GeneratePlantAdviceJob;
use App\Models\AdviceRequest;
use App\Models\Plant;
use App\Models\PlantRecommendation;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

function advicePayload(array $overrides = []): array
{
    return array_merge([
        'customer_name' => 'Camille',
        'environment' => PlantEnvironment::INDOOR->value,
        'exposure' => Exposure::PARTIAL_SHADE->value,
        'space_size' => SpaceSize::MEDIUM->value,
        'maintenance_availability' => Level::LOW->value,
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
        ->and($request->customer_name)->toBe('Camille');
});

test('the optional visitor name can be omitted', function () {
    $response = $this->post(route('advice.store'), advicePayload([
        'customer_name' => null,
    ]));

    $response->assertRedirect();

    $request = AdviceRequest::query()->sole();

    expect($request->customer_name)->toBeNull();
});

test('the public advice form does not collect an email address', function () {
    $this->get(route('advice.create'))
        ->assertOk()
        ->assertDontSee('customer_email')
        ->assertDontSee('Email de suivi');
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
        $this->post(route('advice.store'), advicePayload())->assertRedirect();
    }

    $this->post(route('advice.store'), advicePayload())->assertTooManyRequests();
});

test('a valid public token opens the tracking page without exposing the numeric id', function () {
    $adviceRequest = AdviceRequest::factory()->create();
    $trackingUrl = route('advice.track', ['token' => $adviceRequest->public_token]);

    expect(parse_url($trackingUrl, PHP_URL_PATH))
        ->toMatch('/^\/conseil\/suivi\/[a-f0-9]{64}$/');

    $this->get($trackingUrl)
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertSee('Votre conseil prend racine.')
        ->assertSee($adviceRequest->status->label())
        ->assertDontSee('customer_email');
});

test('the public status endpoint exposes only safe status data', function () {
    $adviceRequest = AdviceRequest::factory()->create();

    $this->getJson(route('advice.status', ['token' => $adviceRequest->public_token]))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJson([
            'status' => AdviceRequestStatus::PENDING->value,
            'label' => AdviceRequestStatus::PENDING->label(),
            'terminal' => false,
        ])
        ->assertJsonMissingPath('customer_email')
        ->assertJsonMissingPath('free_text_description');
});

test('a completed request displays persisted recommendations and escapes advisor text', function () {
    $plant = Plant::factory()->create([
        'name' => 'Calathea test',
        'price' => '31.90',
        'stock_quantity' => 2,
    ]);
    $adviceRequest = AdviceRequest::factory()->completed()->create([
        'space_summary' => '<script>alert("summary")</script>',
        'general_advice' => 'Gardez une lumière douce.',
    ]);
    PlantRecommendation::factory()->create([
        'advice_request_id' => $adviceRequest,
        'plant_id' => $plant,
        'reason' => '<img src=x onerror=alert(1)> Très adaptée.',
        'stock_quantity_snapshot' => 5,
    ]);

    $this->get(route('advice.track', ['token' => $adviceRequest->public_token]))
        ->assertOk()
        ->assertSee('Votre sélection est prête.')
        ->assertSee('Calathea test')
        ->assertSee('Stock observé')
        ->assertSee('&lt;script&gt;alert(&quot;summary&quot;)&lt;/script&gt;', escape: false)
        ->assertDontSee('<script>', escape: false)
        ->assertDontSee('<img src=x onerror=alert(1)>', escape: false);
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
