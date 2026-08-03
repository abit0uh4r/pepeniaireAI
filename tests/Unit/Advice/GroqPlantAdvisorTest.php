<?php

use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Models\Plant;
use App\Services\GroqPlantAdvisor;
use App\Services\PlantAdvisor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function groqAdviceContext(): array
{
    return [
        'environment' => PlantEnvironment::INDOOR->value,
        'exposure' => Exposure::PARTIAL_SHADE->value,
        'space_size' => SpaceSize::MEDIUM->value,
        'maintenance_availability' => Level::MEDIUM->value,
        'free_text_description' => 'Une pièce lumineuse avec un entretien régulier.',
    ];
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
        'exposure' => Exposure::PARTIAL_SHADE,
        'watering_level' => Level::MEDIUM,
        'maintenance_level' => Level::LOW,
        'adult_height_cm' => 80,
        'adult_width_cm' => 50,
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

    $result = groqAdvisor()->advise(groqAdviceContext(), new Collection([$plant]));

    expect($result['recommendations'][0]['plant_id'])->toBe($plant->id);

    Http::assertSent(function (Request $request) use ($plant): bool {
        $body = $request->data();
        $systemMessage = $body['messages'][0]['content'];
        $message = $body['messages'][1]['content'];

        return $body['model'] === 'openai/gpt-oss-20b'
            && $body['response_format']['type'] === 'json_schema'
            && str_contains($systemMessage, 'exclusivement en français')
            && str_contains($message, 'Ficus test')
            && str_contains($message, (string) $plant->id)
            && ! str_contains($message, 'customer_email')
            && ! str_contains($message, 'price')
            && ! str_contains($message, 'stock_quantity')
            && ! str_contains($message, 'pet_safe');
    });
});

test('it rejects an invalid json response before the job can persist it', function () {
    Http::fake([
        'https://groq.test/*' => Http::response([
            'choices' => [['message' => ['content' => '{invalid']]],
        ]),
    ]);

    expect(fn () => groqAdvisor()->advise(groqAdviceContext(), new Collection))
        ->toThrow(RuntimeException::class, 'JSON valide');
});

test('it converts an http failure into a sanitized runtime error', function () {
    Http::fake([
        'https://groq.test/*' => Http::response(['error' => ['message' => 'secret']], 429),
    ]);

    try {
        groqAdvisor()->advise(groqAdviceContext(), new Collection);
        $message = '';
    } catch (RuntimeException $exception) {
        $message = $exception->getMessage();
    }

    expect($message)->toContain('refusé')->not->toContain('secret');
});

test('it refuses to call groq without an api key', function () {
    Http::fake();
    $advisor = new GroqPlantAdvisor('', 'https://groq.test/openai/v1', 'openai/gpt-oss-20b', 7, 900);

    expect(fn () => $advisor->advise(groqAdviceContext(), new Collection))
        ->toThrow(RuntimeException::class, 'pas configuré');

    Http::assertNothingSent();
});
