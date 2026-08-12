<?php

use Illuminate\Support\Facades\Schema;

test('the mvp schema excludes deferred advice and pet fields', function () {
    expect(Schema::hasColumn('plants', 'exposure'))->toBeTrue()
        ->and(Schema::hasColumn('plants', 'pet_safe'))->toBeFalse()
        ->and(Schema::hasColumn('advice_requests', 'has_pets'))->toBeFalse()
        ->and(Schema::hasColumn('advice_requests', 'customer_email'))->toBeFalse()
        ->and(Schema::hasColumn('advice_requests', 'customer_phone'))->toBeTrue()
        ->and(Schema::hasColumn('advice_requests', 'failure_code'))->toBeFalse()
        ->and(Schema::hasColumn('advice_requests', 'raw_ai_response'))->toBeFalse()
        ->and(Schema::hasColumn('plant_recommendations', 'price_snapshot'))->toBeFalse()
        ->and(Schema::hasColumn('plant_recommendations', 'stock_quantity_snapshot'))->toBeTrue();
});
