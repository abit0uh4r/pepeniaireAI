<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\GroqPlantAdvisor;
use App\Services\PlantAdvisor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PlantAdvisor::class, fn (): PlantAdvisor => new GroqPlantAdvisor(
            model: (string) config('advice.groq.model'),
            timeout: (int) config('advice.groq.timeout', 30),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
