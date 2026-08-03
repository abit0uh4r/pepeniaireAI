<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AdviceFailure;
use App\Enums\AdviceRequestStatus;
use App\Models\AdviceRequest;
use App\Models\Plant;
use App\Models\PlantRecommendation;
use App\Services\PlantAdvisor;
use App\Services\PlantEligibilityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class GeneratePlantAdviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 90;

    public function __construct(public int $adviceRequestId) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(
        PlantAdvisor $plantAdvisor,
        PlantEligibilityService $eligibilityService,
    ): void {
        $adviceRequest = AdviceRequest::query()->find($this->adviceRequestId);

        if ($adviceRequest === null || $adviceRequest->status->isTerminal()) {
            return;
        }

        if ($adviceRequest->status === AdviceRequestStatus::PENDING && ! $this->claim($adviceRequest)) {
            return;
        }

        $adviceRequest->refresh();
        $candidatePlants = $eligibilityService->eligiblePlants($adviceRequest);

        if ($candidatePlants->isEmpty()) {
            $this->failRequest($adviceRequest, AdviceFailure::NO_ELIGIBLE_PLANTS);

            return;
        }

        $result = $plantAdvisor->advise($this->advisorContext($adviceRequest), $candidatePlants);
        $validatedResult = $this->validateAdvisorResult($result, $candidatePlants->modelKeys());

        DB::transaction(function () use ($eligibilityService, $validatedResult): void {
            $adviceRequest = AdviceRequest::query()
                ->whereKey($this->adviceRequestId)
                ->lockForUpdate()
                ->first();

            if ($adviceRequest === null || $adviceRequest->status !== AdviceRequestStatus::PROCESSING) {
                return;
            }

            $plantIds = array_column($validatedResult['recommendations'], 'plant_id');
            $plants = Plant::query()
                ->whereKey($plantIds)
                ->lockForUpdate()
                ->get()
                ->filter(fn (Plant $plant): bool => $eligibilityService->isEligible($plant, $adviceRequest))
                ->keyBy(fn (Plant $plant): int => $plant->getKey());

            foreach ($validatedResult['recommendations'] as $recommendation) {
                $plant = $plants->get($recommendation['plant_id']);

                if ($plant === null) {
                    continue;
                }

                PlantRecommendation::query()->create([
                    'advice_request_id' => $adviceRequest->getKey(),
                    'plant_id' => $plant->getKey(),
                    'rank' => $recommendation['rank'],
                    'reason' => $recommendation['reason'],
                    'stock_quantity_snapshot' => $plant->stock_quantity,
                ]);
            }

            $adviceRequest->update([
                'status' => AdviceRequestStatus::COMPLETED->value,
                'space_summary' => $validatedResult['space_summary'],
                'general_advice' => $validatedResult['general_advice'],
                'failure_message' => null,
                'processed_at' => now(),
            ]);
        });
    }

    public function failed(?Throwable $exception): void
    {
        $adviceRequest = AdviceRequest::query()->find($this->adviceRequestId);

        if ($adviceRequest !== null && $adviceRequest->status === AdviceRequestStatus::PROCESSING) {
            $this->failRequest($adviceRequest, AdviceFailure::AI_ERROR);
        }
    }

    private function claim(AdviceRequest $adviceRequest): bool
    {
        return AdviceRequest::query()
            ->whereKey($adviceRequest->getKey())
            ->where('status', AdviceRequestStatus::PENDING->value)
            ->update([
                'status' => AdviceRequestStatus::PROCESSING->value,
                'processing_started_at' => now(),
            ]) === 1;
    }

    /**
     * @return array{environment: string, exposure: string, space_size: string, maintenance_availability: string, free_text_description: string}
     */
    private function advisorContext(AdviceRequest $adviceRequest): array
    {
        return [
            'environment' => $adviceRequest->environment->value,
            'exposure' => $adviceRequest->exposure->value,
            'space_size' => $adviceRequest->space_size->value,
            'maintenance_availability' => $adviceRequest->maintenance_availability->value,
            'free_text_description' => $adviceRequest->free_text_description,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  list<int|string>  $candidatePlantIds
     * @return array{space_summary: string, general_advice: string, recommendations: list<array{plant_id: int, rank: int, reason: string}>}
     */
    private function validateAdvisorResult(array $result, array $candidatePlantIds): array
    {
        if (! is_string($result['space_summary'] ?? null)
            || ! is_string($result['general_advice'] ?? null)
            || ! is_array($result['recommendations'] ?? null)) {
            throw new RuntimeException('La réponse du conseiller est invalide.');
        }

        $candidatePlantIds = array_map(static fn (int|string $id): int => (int) $id, $candidatePlantIds);
        $maxRecommendations = max(1, (int) config('advice.max_recommendations', 3));
        $seenPlantIds = [];
        $recommendations = [];

        foreach ($result['recommendations'] as $recommendation) {
            if (! is_array($recommendation)
                || ! is_int($recommendation['plant_id'] ?? null)
                || ! is_int($recommendation['rank'] ?? null)
                || ! is_string($recommendation['reason'] ?? null)) {
                continue;
            }

            $plantId = $recommendation['plant_id'];
            $rank = $recommendation['rank'];
            $reason = trim($recommendation['reason']);

            if (! in_array($plantId, $candidatePlantIds, true)
                || in_array($plantId, $seenPlantIds, true)
                || $rank < 1
                || $rank > $maxRecommendations
                || $reason === '') {
                continue;
            }

            $seenPlantIds[] = $plantId;
            $recommendations[] = [
                'plant_id' => $plantId,
                'rank' => $rank,
                'reason' => mb_substr($reason, 0, 2000),
            ];

            if (count($recommendations) >= $maxRecommendations) {
                break;
            }
        }

        if ($recommendations === []) {
            throw new RuntimeException('La réponse du conseiller ne contient aucune recommandation valide.');
        }

        usort($recommendations, static fn (array $left, array $right): int => $left['rank'] <=> $right['rank']);

        return [
            'space_summary' => mb_substr(trim($result['space_summary']), 0, 5000),
            'general_advice' => mb_substr(trim($result['general_advice']), 0, 10000),
            'recommendations' => $recommendations,
        ];
    }

    private function failRequest(AdviceRequest $adviceRequest, AdviceFailure $failure): void
    {
        $adviceRequest->update([
            'status' => AdviceRequestStatus::FAILED->value,
            'failure_message' => $failure->message(),
            'processed_at' => now(),
        ]);
    }
}
