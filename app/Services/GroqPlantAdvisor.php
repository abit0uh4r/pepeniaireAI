<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plant;
use Illuminate\Database\Eloquent\Collection;
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
     * @param  array{environment: string, exposure: string, space_size: string, maintenance_availability: string, free_text_description: string}  $context
     * @param  Collection<int, Plant>  $candidatePlants
     * @return array<string, mixed>
     */
    public function advise(array $context, Collection $candidatePlants): array
    {
        if (trim($this->apiKey) === '') {
            throw new RuntimeException('Le fournisseur Groq n’est pas configuré.');
        }

        $response = $this->httpClient()->post('/chat/completions', [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un conseiller botanique. Laravel fournit la liste authoritative des candidates. Retourne uniquement un objet JSON conforme au schéma demandé. Utilise exclusivement les identifiants fournis et n’invente aucune plante ni propriété botanique.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'request' => $context,
                        'candidate_plants' => $this->candidatePlants($candidatePlants),
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
        ]);

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

        if (! is_array($decoded)) {
            throw new RuntimeException('La réponse Groq n’est pas un objet JSON.');
        }

        return $decoded;
    }

    /**
     * @param  Collection<int, Plant>  $candidatePlants
     * @return list<array<string, mixed>>
     */
    private function candidatePlants(Collection $candidatePlants): array
    {
        return $candidatePlants
            ->map(static fn (Plant $plant): array => [
                'id' => $plant->getKey(),
                'name' => $plant->name,
                'species' => $plant->species,
                'description' => $plant->description,
                'environment' => $plant->environment->value,
                'exposure' => $plant->exposure->value,
                'watering_level' => $plant->watering_level->value,
                'maintenance_level' => $plant->maintenance_level->value,
                'adult_height_cm' => $plant->adult_height_cm,
                'adult_width_cm' => $plant->adult_width_cm,
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
}
