<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Models\Plant;
use Illuminate\Database\Seeder;

class PlantSeeder extends Seeder
{
    public function run(): void
    {
        $plants = [
            [
                'name' => 'Monstera deliciosa',
                'species' => 'Monstera deliciosa',
                'description' => 'Une plante graphique aux grandes feuilles découpées.',
                'environment' => PlantEnvironment::INDOOR,
                'exposure' => [Exposure::PARTIAL_SHADE, Exposure::SHADE],
                'watering_level' => Level::MEDIUM,
                'maintenance_level' => Level::MEDIUM,
                'adult_height_cm' => 180,
                'adult_width_cm' => 100,
                'pet_safe' => false,
                'price' => '34.90',
                'stock_quantity' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'Sansevieria Laurentii',
                'species' => 'Dracaena trifasciata',
                'description' => 'Une plante robuste qui tolère les oublis d’arrosage.',
                'environment' => PlantEnvironment::BOTH,
                'exposure' => [Exposure::SUN, Exposure::PARTIAL_SHADE, Exposure::SHADE],
                'watering_level' => Level::LOW,
                'maintenance_level' => Level::LOW,
                'adult_height_cm' => 90,
                'adult_width_cm' => 35,
                'pet_safe' => false,
                'price' => '19.90',
                'stock_quantity' => 14,
                'is_active' => true,
            ],
            [
                'name' => 'Calathea Orbifolia',
                'species' => 'Goeppertia orbifolia',
                'description' => 'Un feuillage décoratif pour une lumière douce et régulière.',
                'environment' => PlantEnvironment::INDOOR,
                'exposure' => [Exposure::SHADE, Exposure::PARTIAL_SHADE],
                'watering_level' => Level::HIGH,
                'maintenance_level' => Level::HIGH,
                'adult_height_cm' => 70,
                'adult_width_cm' => 60,
                'pet_safe' => true,
                'price' => '29.90',
                'stock_quantity' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($plants as $plant) {
            Plant::query()->updateOrCreate(['species' => $plant['species']], $plant);
        }
    }
}
