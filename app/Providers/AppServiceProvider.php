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
                    model: (string) config('advice.groq.model'),
                    timeout: (int) config('advice.groq.timeout', 30),
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
