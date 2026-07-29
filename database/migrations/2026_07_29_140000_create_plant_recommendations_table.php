<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advice_request_id')->constrained('advice_requests')->restrictOnDelete();
            $table->foreignId('plant_id')->constrained('plants')->restrictOnDelete();
            $table->unsignedTinyInteger('rank');
            $table->text('reason');
            $table->decimal('price_snapshot', 10, 2);
            $table->unsignedInteger('stock_quantity_snapshot');
            $table->timestamps();

            $table->unique(['advice_request_id', 'plant_id']);
            $table->index(['advice_request_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_recommendations');
    }
};
