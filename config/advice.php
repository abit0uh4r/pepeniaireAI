<?php

return [
    'ai_provider' => env('AI_PROVIDER', 'fake'),
    'max_recommendations' => (int) env('ADVICE_MAX_RECOMMENDATIONS', 3),
    'groq' => [
        'api_key' => env('GROQ_API_KEY', ''),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
        'timeout' => (int) env('GROQ_TIMEOUT', 30),
        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 1200),
    ],
    'eligibility' => [
        'space_limits' => [
            'SMALL' => ['height_cm' => 60, 'width_cm' => 45],
            'MEDIUM' => ['height_cm' => 120, 'width_cm' => 80],
            'LARGE' => ['height_cm' => 240, 'width_cm' => 160],
        ],
    ],
];
