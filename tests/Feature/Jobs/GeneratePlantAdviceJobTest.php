<?php

use App\Contracts\AI\PlantAdvisor;
use App\DTOs\Advice\AdviceResult;
use App\Enums\AdviceRequestStatus;
use App\Jobs\GeneratePlantAdviceJob;
use App\Models\AdviceRequest;
use Mockery\MockInterface;

test('the job claims a pending request and completes it after the fake advisor runs', function () {
    $adviceRequest = AdviceRequest::factory()->create();

    $advisor = Mockery::mock(PlantAdvisor::class, function (MockInterface $mock): void {
        $mock->shouldReceive('advise')
            ->once()
            ->andReturn(new AdviceResult('Résumé', 'Conseil', []));
    });

    $job = new GeneratePlantAdviceJob($adviceRequest->id, [10, 11]);
    $job->handle($advisor);

    $adviceRequest->refresh();

    expect($adviceRequest->status)->toBe(AdviceRequestStatus::COMPLETED)
        ->and($adviceRequest->processing_started_at)->not->toBeNull()
        ->and($adviceRequest->processed_at)->not->toBeNull()
        ->and($adviceRequest->raw_ai_response)->toBeNull();
});

test('the job completes an empty candidate set without calling the advisor', function () {
    $adviceRequest = AdviceRequest::factory()->create();
    $advisor = Mockery::mock(PlantAdvisor::class);
    $advisor->shouldNotReceive('advise');

    (new GeneratePlantAdviceJob($adviceRequest->id))->handle($advisor);

    expect($adviceRequest->refresh()->status)->toBe(AdviceRequestStatus::COMPLETED);
});

test('the job is idempotent for a terminal request', function () {
    $adviceRequest = AdviceRequest::factory()->completed()->create();
    $advisor = Mockery::mock(PlantAdvisor::class);
    $advisor->shouldNotReceive('advise');

    (new GeneratePlantAdviceJob($adviceRequest->id, [10]))->handle($advisor);

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
