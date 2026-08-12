<?php

use App\Enums\AdviceFailure;
use App\Enums\AdviceRequestStatus;
use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Jobs\GeneratePlantAdviceJob;
use App\Models\AdviceRequest;
use App\Models\Plant;
use App\Models\PlantRecommendation;
use App\Services\PlantAdvisor;
use App\Services\PlantEligibilityService;
use Illuminate\Database\Eloquent\Collection;
use Mockery\MockInterface;

function jobAdviceRequest(array $overrides = []): AdviceRequest
{
    return AdviceRequest::factory()->create(array_merge([
        'environment' => PlantEnvironment::INDOOR,
        'exposure' => Exposure::PARTIAL_SHADE,
        'space_size' => SpaceSize::MEDIUM,
        'maintenance_availability' => Level::MEDIUM,
    ], $overrides));
}

function jobEligiblePlant(array $overrides = []): Plant
{
    return Plant::factory()->create(array_merge([
        'environment' => PlantEnvironment::INDOOR,
        'exposure' => Exposure::PARTIAL_SHADE,
        'maintenance_level' => Level::LOW,
        'adult_height_cm' => 80,
        'adult_width_cm' => 50,
        'stock_quantity' => 5,
    ], $overrides));
}

function validAdvisorResult(Plant $plant): array
{
    return [
        'space_summary' => 'Résumé',
        'general_advice' => 'Conseil',
        'recommendations' => [[
            'plant_id' => $plant->id,
            'rank' => 1,
            'reason' => 'Bonne adaptation.',
        ]],
    ];
}

test('the job claims a pending request and persists only the quantity snapshot', function () {
    $adviceRequest = jobAdviceRequest();
    $plant = jobEligiblePlant(['price' => '25.00', 'stock_quantity' => 5]);

    $advisor = Mockery::mock(PlantAdvisor::class, function (MockInterface $mock) use ($plant): void {
        $mock->shouldReceive('advise')
            ->once()
            ->withArgs(fn (array $context, Collection $candidates): bool => $candidates->modelKeys() === [$plant->id]
                && ! array_key_exists('customer_email', $context)
                && ! array_key_exists('customer_phone', $context))
            ->andReturn(validAdvisorResult($plant));
    });

    (new GeneratePlantAdviceJob($adviceRequest->id))->handle(
        $advisor,
        app(PlantEligibilityService::class),
    );

    $adviceRequest->refresh();
    $recommendation = PlantRecommendation::query()->sole();

    expect($adviceRequest->status)->toBe(AdviceRequestStatus::COMPLETED)
        ->and($adviceRequest->processing_started_at)->not->toBeNull()
        ->and($adviceRequest->processed_at)->not->toBeNull()
        ->and($recommendation->plant_id)->toBe($plant->id)
        ->and($recommendation->stock_quantity_snapshot)->toBe(5);

    $plant->update(['price' => '30.00', 'stock_quantity' => 2]);

    expect($recommendation->refresh()->stock_quantity_snapshot)->toBe(5)
        ->and($plant->refresh()->stock_quantity)->toBe(2);
});

test('the job records no eligible plants without calling the advisor', function () {
    $adviceRequest = jobAdviceRequest();
    $advisor = Mockery::mock(PlantAdvisor::class);
    $advisor->shouldNotReceive('advise');

    (new GeneratePlantAdviceJob($adviceRequest->id))->handle(
        $advisor,
        app(PlantEligibilityService::class),
    );

    expect($adviceRequest->refresh()->status)->toBe(AdviceRequestStatus::FAILED)
        ->and($adviceRequest->failure_message)->toBe(AdviceFailure::NO_ELIGIBLE_PLANTS->message());
});

test('the job validates invented identifiers duplicates and limits before persistence', function () {
    $adviceRequest = jobAdviceRequest();
    $first = jobEligiblePlant();
    $second = jobEligiblePlant();
    $third = jobEligiblePlant();
    $fourth = jobEligiblePlant();

    $advisor = Mockery::mock(PlantAdvisor::class);
    $advisor->shouldReceive('advise')->once()->andReturn([
        'space_summary' => ' Résumé contrôlé ',
        'general_advice' => ' Conseil contrôlé ',
        'recommendations' => [
            ['plant_id' => 999999, 'rank' => 1, 'reason' => 'Inventée'],
            ['plant_id' => $first->id, 'rank' => 1, 'reason' => 'Premier choix'],
            ['plant_id' => $first->id, 'rank' => 2, 'reason' => 'Doublon'],
            ['plant_id' => $second->id, 'rank' => 2, 'reason' => 'Deuxième choix'],
            ['plant_id' => $third->id, 'rank' => 4, 'reason' => 'Rang invalide'],
            ['plant_id' => $fourth->id, 'rank' => 3, 'reason' => 'Troisième choix'],
        ],
    ]);

    (new GeneratePlantAdviceJob($adviceRequest->id))->handle(
        $advisor,
        app(PlantEligibilityService::class),
    );

    expect(PlantRecommendation::query()->orderBy('rank')->pluck('plant_id')->all())
        ->toBe([$first->id, $second->id, $fourth->id])
        ->and($adviceRequest->refresh()->space_summary)->toBe('Résumé contrôlé');
});

test('the job rechecks deterministic eligibility before persistence', function () {
    $adviceRequest = jobAdviceRequest();
    $plant = jobEligiblePlant();
    $advisor = Mockery::mock(PlantAdvisor::class);
    $advisor->shouldReceive('advise')->once()->andReturnUsing(function () use ($plant): array {
        $result = validAdvisorResult($plant);
        $plant->update(['exposure' => Exposure::SUN]);

        return $result;
    });

    (new GeneratePlantAdviceJob($adviceRequest->id))->handle(
        $advisor,
        app(PlantEligibilityService::class),
    );

    expect($adviceRequest->refresh()->status)->toBe(AdviceRequestStatus::COMPLETED)
        ->and(PlantRecommendation::query()->count())->toBe(0);
});

test('the job rejects a malformed advisor response and stores only the public AI error', function () {
    $adviceRequest = jobAdviceRequest();
    jobEligiblePlant();
    $advisor = Mockery::mock(PlantAdvisor::class);
    $advisor->shouldReceive('advise')->once()->andReturn(['unexpected' => 'response']);
    $job = new GeneratePlantAdviceJob($adviceRequest->id);

    try {
        $job->handle($advisor, app(PlantEligibilityService::class));
        $exception = null;
    } catch (RuntimeException $caught) {
        $exception = $caught;
    }

    expect($exception)->toBeInstanceOf(RuntimeException::class);

    $job->failed($exception);

    expect($adviceRequest->refresh()->status)->toBe(AdviceRequestStatus::FAILED)
        ->and($adviceRequest->failure_message)->toBe(AdviceFailure::AI_ERROR->message())
        ->and($adviceRequest->failure_message)->not->toContain('unexpected');
});

test('an automatic retry can resume a processing request', function () {
    $adviceRequest = jobAdviceRequest(['status' => AdviceRequestStatus::PROCESSING]);
    $plant = jobEligiblePlant();
    $advisor = Mockery::mock(PlantAdvisor::class);
    $advisor->shouldReceive('advise')->once()->andReturn(validAdvisorResult($plant));

    (new GeneratePlantAdviceJob($adviceRequest->id))->handle(
        $advisor,
        app(PlantEligibilityService::class),
    );

    expect($adviceRequest->refresh()->status)->toBe(AdviceRequestStatus::COMPLETED)
        ->and(PlantRecommendation::query()->count())->toBe(1);
});

test('the job is idempotent for a terminal request', function () {
    $adviceRequest = AdviceRequest::factory()->completed()->create();
    $advisor = Mockery::mock(PlantAdvisor::class);
    $advisor->shouldNotReceive('advise');

    (new GeneratePlantAdviceJob($adviceRequest->id))->handle(
        $advisor,
        app(PlantEligibilityService::class),
    );

    expect($adviceRequest->refresh()->status)->toBe(AdviceRequestStatus::COMPLETED);
});

test('the job has bounded retry settings', function () {
    $job = new GeneratePlantAdviceJob(1);

    expect($job->tries)->toBe(3)
        ->and($job->timeout)->toBe(90)
        ->and($job->backoff())->toBe([10, 30]);
});
