<?php

use App\Contracts\AI\PlantAdvisor;
use App\DTOs\Advice\AdviceContext;
use App\DTOs\Advice\AdviceResult;
use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Models\Plant;
use App\Services\AI\GroqPlantAdvisor;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function groqAdviceContext(): AdviceContext
{
    return new AdviceContext(
        environment: PlantEnvironment::INDOOR,
        exposure: Exposure::PARTIAL_SHADE,
        spaceSize: SpaceSize::MEDIUM,
        maintenanceAvailability: Level::MEDIUM,
        hasPets: false,
        freeTextDescription: 'Une pièce lumineuse avec un entretien régulier.',
    );
}

function groqAdvisor(): GroqPlantAdvisor
{
    return new GroqPlantAdvisor(
        apiKey: 'test-groq-key',
        baseUrl: 'https://groq.test/openai/v1',
        model: 'openai/gpt-oss-20b',
        timeout: 7,
        maxTokens: 900,
    );
}

test('the groq provider is opt-in and the fake remains the default', function () {
    expect(app(PlantAdvisor::class))->not->toBeInstanceOf(GroqPlantAdvisor::class);

    config()->set('advice.ai_provider', 'groq');

    expect(app(PlantAdvisor::class))->toBeInstanceOf(GroqPlantAdvisor::class);
});

test('it sends only the request context and prefiltered plant fields', function () {
    $plant = Plant::factory()->create([
        'name' => 'Ficus test',
        'species' => 'Ficus elastica',
        'description' => 'Plante de test',
        'environment' => PlantEnvironment::INDOOR,
        'exposure' => [Exposure::PARTIAL_SHADE],
        'watering_level' => Level::MEDIUM,
        'maintenance_level' => Level::LOW,
        'adult_height_cm' => 80,
        'adult_width_cm' => 50,
        'pet_safe' => true,
    ]);
    $content = json_encode([
        'space_summary' => 'Espace moyen',
        'general_advice' => 'Conseil général',
        'recommendations' => [['plant_id' => $plant->id, 'rank' => 1, 'reason' => 'Bonne adaptation.']],
    ], JSON_THROW_ON_ERROR);

    Http::fake([
        'https://groq.test/*' => Http::response([
            'choices' => [['message' => ['content' => $content]]],
        ]),
    ]);

    $result = groqAdvisor()->advise(groqAdviceContext(), [$plant->id]);

    expect($result)->toBeInstanceOf(AdviceResult::class)
        ->and($result->recommendations[0]->plantId)->toBe($plant->id);

    Http::assertSent(function (Request $request) use ($plant): bool {
        $body = $request->data();
        $message = $body['messages'][1]['content'];

        return $body['model'] === 'openai/gpt-oss-20b'
            && $body['response_format']['type'] === 'json_schema'
            && str_contains($message, 'Ficus test')
            && str_contains($message, (string) $plant->id)
            && ! str_contains($message, 'customer_email')
            && ! str_contains($message, 'price')
            && ! str_contains($message, 'stock_quantity');
    });
});

test('it rejects an invalid json response before the job can persist it', function () {
    Http::fake([
        'https://groq.test/*' => Http::response([
            'choices' => [['message' => ['content' => '{invalid']]],
        ]),
    ]);

    expect(fn () => groqAdvisor()->advise(groqAdviceContext(), [1]))
        ->toThrow(RuntimeException::class, 'JSON valide');
});

test('it converts an http failure into a sanitized runtime error', function () {
    Http::fake([
        'https://groq.test/*' => Http::response(['error' => ['message' => 'secret']], 429),
    ]);

    try {
        groqAdvisor()->advise(groqAdviceContext(), [1]);
        $message = '';
    } catch (RuntimeException $exception) {
        $message = $exception->getMessage();
    }

    expect($message)->toContain('refusé')->not->toContain('secret');
});

test('it refuses to call groq without an api key', function () {
    Http::fake();
    $advisor = new GroqPlantAdvisor('', 'https://groq.test/openai/v1', 'openai/gpt-oss-20b', 7, 900);

    expect(fn () => $advisor->advise(groqAdviceContext(), [1]))
        ->toThrow(RuntimeException::class, 'pas configuré');

    Http::assertNothingSent();
});
