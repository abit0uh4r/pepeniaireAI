<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plants', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('species', 180);
            $table->text('description')->nullable();
            $table->string('environment', 20);
            $table->json('exposure');
            $table->string('watering_level', 20);
            $table->string('maintenance_level', 20);
            $table->unsignedSmallInteger('adult_height_cm')->nullable();
            $table->unsignedSmallInteger('adult_width_cm')->nullable();
            $table->boolean('pet_safe')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_active', 'stock_quantity', 'environment', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plants');
    }
};
