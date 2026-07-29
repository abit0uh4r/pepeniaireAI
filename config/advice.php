<?php

return [
    'ai_provider' => env('AI_PROVIDER', 'fake'),
    'max_recommendations' => (int) env('ADVICE_MAX_RECOMMENDATIONS', 3),
    'eligibility' => [
        'space_limits' => [
            'SMALL' => ['height_cm' => 60, 'width_cm' => 45],
            'MEDIUM' => ['height_cm' => 120, 'width_cm' => 80],
            'LARGE' => ['height_cm' => 240, 'width_cm' => 160],
        ],
    ],
];
