<?php

use App\Ai\Agents\PlantAdviceAgent;
use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Models\Plant;
use App\Services\GroqPlantAdvisor;
use App\Services\PlantAdvisor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

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
        model: 'openai/gpt-oss-20b',
        timeout: 7,
    );
}

test('the groq provider is opt-in and the fake remains the default', function () {
    expect(app(PlantAdvisor::class))->not->toBeInstanceOf(GroqPlantAdvisor::class);

    config()->set('advice.ai_provider', 'groq');

    expect(app(PlantAdvisor::class))->toBeInstanceOf(GroqPlantAdvisor::class);
});

test('it sends only the request context and prefiltered plant fields to the Laravel AI agent', function () {
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
    PlantAdviceAgent::fake([[
        'space_summary' => 'Espace moyen',
        'general_advice' => 'Conseil général',
        'recommendations' => [['plant_id' => $plant->id, 'rank' => 1, 'reason' => 'Bonne adaptation.']],
    ]])->preventStrayPrompts();

    $result = groqAdvisor()->advise(groqAdviceContext(), new Collection([$plant]));

    expect($result['recommendations'][0]['plant_id'])->toBe($plant->id);

    PlantAdviceAgent::assertPrompted(function ($prompt) use ($plant): bool {
        $payload = json_decode($prompt->prompt, true, 512, JSON_THROW_ON_ERROR);
        $message = $prompt->prompt;

        return $prompt->model === 'openai/gpt-oss-20b'
            && $prompt->provider->name() === 'groq'
            && str_contains($message, 'Ficus test')
            && str_contains($message, (string) $plant->id)
            && ! str_contains($message, 'customer_email')
            && ! str_contains($message, 'price')
            && ! str_contains($message, 'stock_quantity')
            && ! str_contains($message, 'pet_safe')
            && $payload['request'] === groqAdviceContext();
    });
});

test('the agent exposes the structured advice fields', function () {
    $schema = app(JsonSchemaTypeFactory::class);
    $agent = PlantAdviceAgent::make();

    expect($agent->schema($schema))->toHaveKeys([
        'space_summary',
        'general_advice',
        'recommendations',
    ]);
});

test('it converts a provider failure into a sanitized runtime error', function () {
    PlantAdviceAgent::fake([
        fn (): never => throw new RuntimeException('secret provider detail'),
    ]);

    expect(fn () => groqAdvisor()->advise(groqAdviceContext(), new Collection))
        ->toThrow(RuntimeException::class, 'refusé');
});
