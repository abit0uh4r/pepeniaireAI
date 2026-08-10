<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

final class PlantAdviceAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
Tu es un conseiller botanique francophone.

Laravel est la source de vérité : la liste candidate_plants contient les seules plantes que tu as le droit de recommander. Utilise exclusivement leurs identifiants id. N'invente aucune plante, aucun identifiant, aucun stock, aucun prix et aucune propriété botanique absente des candidates.

Réponds en français naturel dans les champs textuels. Classe les candidates selon la demande et explique brièvement chaque choix. Retourne uniquement l'objet conforme au schéma structuré.
INSTRUCTIONS;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'space_summary' => $schema->string()->required(),
            'general_advice' => $schema->string()->required(),
            'recommendations' => $schema->array()
                ->items($schema->object([
                    'plant_id' => $schema->integer()->required(),
                    'rank' => $schema->integer()->required(),
                    'reason' => $schema->string()->required(),
                ]))
                ->required(),
        ];
    }

    public function timeout(): int
    {
        return max(1, (int) config('advice.groq.timeout', 30));
    }

    public function maxTokens(): int
    {
        return max(1, (int) config('advice.groq.max_tokens', 1200));
    }
}
