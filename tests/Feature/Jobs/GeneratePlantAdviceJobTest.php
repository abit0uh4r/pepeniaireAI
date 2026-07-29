<?php

use App\Contracts\AI\PlantAdvisor;
use App\DTOs\Advice\AdviceResult;
use App\DTOs\Advice\PlantRecommendationData;
use App\Enums\AdviceRequestStatus;
use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Jobs\GeneratePlantAdviceJob;
use App\Models\AdviceRequest;
use App\Models\Plant;
use App\Models\PlantRecommendation;
use App\Services\Advice\AdviceResultValidator;
use App\Services\Plants\PlantEligibilityService;
use Mockery\MockInterface;

test('the job claims a pending request and completes it after the fake advisor runs', function () {
    $adviceRequest = AdviceRequest::factory()->create([
        'environment' => PlantEnvironment::INDOOR,
        'exposure' => Exposure::PARTIAL_SHADE,
        'space_size' => SpaceSize::MEDIUM,
        'maintenance_availability' => Level::MEDIUM,
        'has_pets' => false,
    ]);
    $plant = Plant::factory()->create([
        'environment' => PlantEnvironment::INDOOR,
        'exposure' => [Exposure::PARTIAL_SHADE],
        'maintenance_level' => Level::LOW,
        'adult_height_cm' => 80,
        'adult_width_cm' => 50,
        'price' => '25.00',
        'stock_quantity' => 5,
    ]);

    $advisor = Mockery::mock(PlantAdvisor::class, function (MockInterface $mock) use ($plant): void {
        $mock->shouldReceive('advise')
            ->once()
            ->withArgs(fn ($context, array $candidatePlantIds): bool => $candidatePlantIds === [$plant->id])
            ->andReturn(new AdviceResult(
                'Résumé',
                'Conseil',
                [new PlantRecommendationData($plant->id, 1, 'Bonne adaptation.')],
            ));
    });

    $job = new GeneratePlantAdviceJob($adviceRequest->id);
    $job->handle($advisor, app(PlantEligibilityService::class), app(AdviceResultValidator::class));

    $adviceRequest->refresh();

    expect($adviceRequest->status)->toBe(AdviceRequestStatus::COMPLETED)
        ->and($adviceRequest->processing_started_at)->not->toBeNull()
        ->and($adviceRequest->processed_at)->not->toBeNull()
        ->and($adviceRequest->raw_ai_response)->toBeArray();

    $recommendation = PlantRecommendation::query()->sole();

    expect($recommendation->plant_id)->toBe($plant->id)
        ->and($recommendation->rank)->toBe(1)
        ->and($recommendation->price_snapshot)->toBe('25.00')
        ->and($recommendation->stock_quantity_snapshot)->toBe(5);

    $plant->update(['price' => '30.00', 'stock_quantity' => 2]);

    expect($recommendation->refresh()->price_snapshot)->toBe('25.00')
        ->and($recommendation->stock_quantity_snapshot)->toBe(5)
        ->and($plant->refresh()->stock_quantity)->toBe(2);
});

test('the job completes an empty candidate set without calling the advisor', function () {
    $adviceRequest = AdviceRequest::factory()->create();
    $advisor = Mockery::mock(PlantAdvisor::class);
    $advisor->shouldNotReceive('advise');

    (new GeneratePlantAdviceJob($adviceRequest->id))->handle($advisor, app(PlantEligibilityService::class), app(AdviceResultValidator::class));

    expect($adviceRequest->refresh()->status)->toBe(AdviceRequestStatus::COMPLETED);
});

test('the job is idempotent for a terminal request', function () {
    $adviceRequest = AdviceRequest::factory()->completed()->create();
    $advisor = Mockery::mock(PlantAdvisor::class);
    $advisor->shouldNotReceive('advise');

    (new GeneratePlantAdviceJob($adviceRequest->id))->handle($advisor, app(PlantEligibilityService::class), app(AdviceResultValidator::class));

    expect($adviceRequest->refresh()->status)->toBe(AdviceRequestStatus::COMPLETED);
});

test('the failed job transition records a stable public failure state', function () {
    $adviceRequest = AdviceRequest::factory()->create(['status' => AdviceRequestStatus::PROCESSING]);

    (new GeneratePlantAdviceJob($adviceRequest->id))->failed(new RuntimeException('internal details'));

    $adviceRequest->refresh();

    expect($adviceRequest->status)->toBe(AdviceRequestStatus::FAILED)
        ->and($adviceRequest->failure_code)->toBe('ADVISOR_FAILED')
        ->and($adviceRequest->failure_message)->toBe('Le traitement du conseil a échoué.')
        ->and($adviceRequest->failure_message)->not->toContain('internal details');
});

test('the job has bounded retry settings', function () {
    $job = new GeneratePlantAdviceJob(1);

    expect($job->tries)->toBe(3)
        ->and($job->timeout)->toBe(90)
        ->and($job->backoff())->toBe([10, 30]);
});
