<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\AI\PlantAdvisor;
use App\DTOs\Advice\AdviceContext;
use App\Enums\AdviceRequestStatus;
use App\Models\AdviceRequest;
use App\Models\Plant;
use App\Models\PlantRecommendation;
use App\Services\Advice\AdviceResultValidator;
use App\Services\Plants\PlantEligibilityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

final class GeneratePlantAdviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 90;

    public function __construct(
        public int $adviceRequestId,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(
        PlantAdvisor $plantAdvisor,
        PlantEligibilityService $eligibilityService,
        AdviceResultValidator $resultValidator,
    ): void {
        $adviceRequest = AdviceRequest::query()->find($this->adviceRequestId);

        if ($adviceRequest === null || $adviceRequest->status !== AdviceRequestStatus::PENDING) {
            return;
        }

        $claimed = AdviceRequest::query()
            ->whereKey($adviceRequest->getKey())
            ->where('status', AdviceRequestStatus::PENDING->value)
            ->update([
                'status' => AdviceRequestStatus::PROCESSING->value,
                'processing_started_at' => now(),
            ]);

        if ($claimed !== 1) {
            return;
        }

        $adviceRequest->refresh();
        $context = AdviceContext::fromRequest($adviceRequest);
        $candidatePlantIds = $eligibilityService->eligiblePlantIds($context);

        if ($candidatePlantIds === []) {
            $this->completeWithoutRecommendations($adviceRequest);

            return;
        }

        $result = $plantAdvisor->advise($context, $candidatePlantIds);
        $validatedResult = $resultValidator->validate($result, $candidatePlantIds);

        DB::transaction(function () use ($validatedResult): void {
            $adviceRequest = AdviceRequest::query()
                ->whereKey($this->adviceRequestId)
                ->lockForUpdate()
                ->first();

            if ($adviceRequest === null || $adviceRequest->status !== AdviceRequestStatus::PROCESSING) {
                return;
            }

            $recommendationIds = array_map(
                static fn ($recommendation): int => $recommendation->plantId,
                $validatedResult->recommendations,
            );
            $plants = Plant::query()
                ->whereKey($recommendationIds)
                ->lockForUpdate()
                ->get()
                ->filter(fn (Plant $plant): bool => $plant->is_active && $plant->stock_quantity > 0)
                ->keyBy(fn (Plant $plant): int => $plant->getKey());

            foreach ($validatedResult->recommendations as $recommendation) {
                $plant = $plants->get($recommendation->plantId);

                if ($plant === null) {
                    continue;
                }

                PlantRecommendation::query()->create([
                    'advice_request_id' => $adviceRequest->getKey(),
                    'plant_id' => $plant->getKey(),
                    'rank' => $recommendation->rank,
                    'reason' => $recommendation->reason,
                    'price_snapshot' => $plant->price,
                    'stock_quantity_snapshot' => $plant->stock_quantity,
                ]);
            }

            $adviceRequest->update([
                'status' => AdviceRequestStatus::COMPLETED->value,
                'space_summary' => $validatedResult->spaceSummary,
                'general_advice' => $validatedResult->generalAdvice,
                'raw_ai_response' => $validatedResult->toArray(),
                'processed_at' => now(),
            ]);
        });
    }

    private function completeWithoutRecommendations(AdviceRequest $adviceRequest): void
    {
        $adviceRequest->update([
            'status' => AdviceRequestStatus::COMPLETED->value,
            'space_summary' => 'Aucune plante ne correspond aux critères indiqués.',
            'general_advice' => null,
            'raw_ai_response' => [
                'space_summary' => 'Aucune plante ne correspond aux critères indiqués.',
                'general_advice' => '',
                'recommendations' => [],
            ],
            'processed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        AdviceRequest::query()
            ->whereKey($this->adviceRequestId)
            ->where('status', AdviceRequestStatus::PROCESSING->value)
            ->update([
                'status' => AdviceRequestStatus::FAILED->value,
                'failure_code' => 'ADVISOR_FAILED',
                'failure_message' => 'Le traitement du conseil a échoué.',
                'processed_at' => now(),
            ]);
    }
}
