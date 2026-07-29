<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\AI\PlantAdvisor;
use App\DTOs\Advice\AdviceContext;
use App\DTOs\Advice\AdviceResult;
use App\DTOs\Advice\PlantRecommendationData;
use App\Models\Plant;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

final class GroqPlantAdvisor implements PlantAdvisor
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $model,
        private readonly int $timeout,
        private readonly int $maxTokens,
    ) {}

    /**
     * @param  list<int>  $candidatePlantIds
     */
    public function advise(AdviceContext $context, array $candidatePlantIds): AdviceResult
    {
        if (trim($this->apiKey) === '') {
            throw new RuntimeException('Le fournisseur Groq n’est pas configuré.');
        }

        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un conseiller botanique. Laravel fournit la liste authoritative des candidates. Retourne uniquement un objet JSON conforme au schéma demandé. Utilise exclusivement les identifiants fournis et n’invente aucune plante ni propriété botanique.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'request' => $context->toAdvisorPayload(),
                        'candidate_plants' => $this->candidatePlants($candidatePlantIds),
                    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                ],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'plant_advice',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'space_summary' => ['type' => 'string'],
                            'general_advice' => ['type' => 'string'],
                            'recommendations' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'properties' => [
                                        'plant_id' => ['type' => 'integer'],
                                        'rank' => ['type' => 'integer'],
                                        'reason' => ['type' => 'string'],
                                    ],
                                    'required' => ['plant_id', 'rank', 'reason'],
                                ],
                            ],
                        ],
                        'required' => ['space_summary', 'general_advice', 'recommendations'],
                    ],
                ],
            ],
            'max_tokens' => $this->maxTokens,
        ];

        $response = $this->httpClient()->post('/chat/completions', $payload);

        if ($response->failed()) {
            throw new RuntimeException('Le fournisseur Groq a refusé la demande.');
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('La réponse Groq est vide ou mal formée.');
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('La réponse Groq n’est pas un JSON valide.', previous: $exception);
        }

        return $this->toAdviceResult($decoded);
    }

    /** @return list<array<string, mixed>> */
    private function candidatePlants(array $candidatePlantIds): array
    {
        return Plant::query()
            ->whereKey(array_values(array_unique($candidatePlantIds)))
            ->get()
            ->map(static fn (Plant $plant): array => [
                'id' => $plant->getKey(),
                'name' => $plant->name,
                'species' => $plant->species,
                'description' => $plant->description,
                'environment' => $plant->environment->value,
                'exposure' => $plant->exposureValues()->map(static fn ($exposure): string => $exposure->value)->values()->all(),
                'watering_level' => $plant->watering_level->value,
                'maintenance_level' => $plant->maintenance_level->value,
                'adult_height_cm' => $plant->adult_height_cm,
                'adult_width_cm' => $plant->adult_width_cm,
                'pet_safe' => $plant->pet_safe,
            ])
            ->values()
            ->all();
    }

    private function httpClient(): PendingRequest
    {
        return Http::asJson()
            ->acceptJson()
            ->withToken($this->apiKey)
            ->timeout($this->timeout)
            ->baseUrl(rtrim($this->baseUrl, '/'));
    }

    private function toAdviceResult(mixed $decoded): AdviceResult
    {
        if (! is_array($decoded)
            || ! is_string($decoded['space_summary'] ?? null)
            || ! is_string($decoded['general_advice'] ?? null)
            || ! is_array($decoded['recommendations'] ?? null)) {
            throw new RuntimeException('La réponse Groq ne respecte pas le schéma attendu.');
        }

        $recommendations = [];

        foreach ($decoded['recommendations'] as $recommendation) {
            if (! is_array($recommendation)
                || ! is_int($recommendation['plant_id'] ?? null)
                || ! is_int($recommendation['rank'] ?? null)
                || ! is_string($recommendation['reason'] ?? null)) {
                throw new RuntimeException('La réponse Groq contient une recommandation invalide.');
            }

            $recommendations[] = new PlantRecommendationData(
                plantId: $recommendation['plant_id'],
                rank: $recommendation['rank'],
                reason: $recommendation['reason'],
            );
        }

        return new AdviceResult(
            spaceSummary: $decoded['space_summary'],
            generalAdvice: $decoded['general_advice'],
            recommendations: $recommendations,
        );
    }
}
