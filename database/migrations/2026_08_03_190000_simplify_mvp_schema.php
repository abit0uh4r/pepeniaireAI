<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const EXPOSURES = ['SUN', 'PARTIAL_SHADE', 'SHADE'];

    public function up(): void
    {
        Schema::table('plants', function (Blueprint $table): void {
            $table->enum('exposure_value', self::EXPOSURES)->nullable()->after('exposure');
        });

        DB::table('plants')
            ->select(['id', 'exposure'])
            ->orderBy('id')
            ->get()
            ->each(function (object $plant): void {
                $decoded = json_decode((string) $plant->exposure, true);
                $exposure = is_array($decoded) ? ($decoded[0] ?? null) : $plant->exposure;

                DB::table('plants')->where('id', $plant->id)->update([
                    'exposure_value' => in_array($exposure, self::EXPOSURES, true)
                        ? $exposure
                        : 'PARTIAL_SHADE',
                ]);
            });

        Schema::table('plants', function (Blueprint $table): void {
            $table->dropColumn(['exposure', 'pet_safe']);
        });

        Schema::table('plants', function (Blueprint $table): void {
            $table->renameColumn('exposure_value', 'exposure');
        });

        Schema::table('advice_requests', function (Blueprint $table): void {
            $table->dropColumn(['has_pets', 'failure_code', 'raw_ai_response']);
        });

        Schema::table('plant_recommendations', function (Blueprint $table): void {
            $table->dropColumn('price_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table): void {
            $table->json('exposure_values')->nullable()->after('exposure');
        });

        DB::table('plants')
            ->select(['id', 'exposure'])
            ->orderBy('id')
            ->get()
            ->each(function (object $plant): void {
                DB::table('plants')->where('id', $plant->id)->update([
                    'exposure_values' => json_encode([$plant->exposure], JSON_THROW_ON_ERROR),
                ]);
            });

        Schema::table('plants', function (Blueprint $table): void {
            $table->dropColumn('exposure');
        });

        Schema::table('plants', function (Blueprint $table): void {
            $table->renameColumn('exposure_values', 'exposure');
            $table->boolean('pet_safe')->nullable();
        });

        Schema::table('advice_requests', function (Blueprint $table): void {
            $table->boolean('has_pets')->default(false);
            $table->string('failure_code', 80)->nullable();
            $table->json('raw_ai_response')->nullable();
        });

        Schema::table('plant_recommendations', function (Blueprint $table): void {
            $table->decimal('price_snapshot', 10, 2)->default(0);
        });
    }
};
