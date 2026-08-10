<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\PlantAdviceAgent;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Collection;
use JsonException;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;
use Throwable;

final class GroqPlantAdvisor implements PlantAdvisor
{
    public function __construct(
        private readonly string $model,
        private readonly int $timeout,
    ) {}

    /**
     * @param  array{environment: string, exposure: string, space_size: string, maintenance_availability: string, free_text_description: string}  $context
     * @param  Collection<int, Plant>  $candidatePlants
     * @return array<string, mixed>
     */
    public function advise(array $context, Collection $candidatePlants): array
    {
        try {
            $prompt = json_encode([
                'request' => $context,
                'candidate_plants' => $this->candidatePlants($candidatePlants),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            $response = PlantAdviceAgent::make()->prompt(
                $prompt,
                provider: Lab::Groq,
                model: $this->model,
                timeout: $this->timeout,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Le contexte envoyé au fournisseur Groq est invalide.', previous: $exception);
        } catch (Throwable $exception) {
            throw new RuntimeException('Le fournisseur Groq a refusé la demande.', previous: $exception);
        }

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('La réponse Groq n’a pas le format structuré attendu.');
        }

        return $response->toArray();
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
}
