<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\AI\PlantAdvisor;
use App\DTOs\Advice\AdviceContext;
use App\Enums\AdviceRequestStatus;
use App\Models\AdviceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class GeneratePlantAdviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 90;

    /**
     * @param  list<int>  $candidatePlantIds
     */
    public function __construct(
        public int $adviceRequestId,
        public array $candidatePlantIds = [],
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(PlantAdvisor $plantAdvisor): void
    {
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

        if ($this->candidatePlantIds !== []) {
            // Phase 8 will validate and persist this result. Phase 6 only wires the contract and queue.
            $plantAdvisor->advise(
                AdviceContext::fromRequest($adviceRequest),
                $this->candidatePlantIds,
            );
        }

        $adviceRequest->update([
            'status' => AdviceRequestStatus::COMPLETED->value,
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
