<?php

return [
    'ai_provider' => env('AI_PROVIDER', 'fake'),
    'max_recommendations' => (int) env('ADVICE_MAX_RECOMMENDATIONS', 3),
];
