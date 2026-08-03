<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\FakePlantAdvisor;
use App\Services\GroqPlantAdvisor;
use App\Services\PlantAdvisor;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PlantAdvisor::class, function (): PlantAdvisor {
            return match (config('advice.ai_provider')) {
                'fake' => new FakePlantAdvisor((int) config('advice.max_recommendations', 3)),
                'groq' => new GroqPlantAdvisor(
                    apiKey: (string) config('advice.groq.api_key', ''),
                    baseUrl: (string) config('advice.groq.base_url'),
                    model: (string) config('advice.groq.model'),
                    timeout: (int) config('advice.groq.timeout', 30),
                    maxTokens: (int) config('advice.groq.max_tokens', 1200),
                ),
                default => throw new InvalidArgumentException('Unsupported AI provider.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
