<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advice_requests', function (Blueprint $table): void {
            $table->id();
            $table->char('public_token', 64)->unique();
            $table->string('customer_name', 120)->nullable();
            $table->string('customer_email')->nullable();
            $table->string('environment', 20);
            $table->string('exposure', 20);
            $table->string('space_size', 20);
            $table->string('maintenance_availability', 20);
            $table->boolean('has_pets');
            $table->text('free_text_description');
            $table->string('status', 20)->default('PENDING');
            $table->text('space_summary')->nullable();
            $table->json('avoid_items')->nullable();
            $table->text('general_advice')->nullable();
            $table->string('failure_code', 80)->nullable();
            $table->text('failure_message')->nullable();
            $table->json('raw_ai_response')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advice_requests');
    }
};
