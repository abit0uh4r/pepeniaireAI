<?php

use App\Enums\AdviceRequestStatus;
use App\Models\AdviceRequest;

test('advice requests default to pending and generate unique public tokens', function () {
    $first = AdviceRequest::factory()->create();
    $second = AdviceRequest::factory()->create();

    expect($first->status)->toBe(AdviceRequestStatus::PENDING)
        ->and($first->public_token)->not->toBe($second->public_token)
        ->and(strlen($first->public_token))->toBe(64);
});
