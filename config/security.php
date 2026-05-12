<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Endpoint Rate Limits
    |--------------------------------------------------------------------------
    |
    | These values protect public survey endpoints against burst abuse while
    | leaving enough headroom for legitimate institutional usage. Tune them
    | per environment if needed.
    |
    */

    'rate_limits' => [
        'public_survey' => [
            'per_minute' => env('PUBLIC_SURVEY_RATE_LIMIT_PER_MINUTE', 30),
            'per_hour' => env('PUBLIC_SURVEY_RATE_LIMIT_PER_HOUR', 300),
        ],
        'survey_catalogs' => [
            'per_minute' => env('SURVEY_CATALOG_RATE_LIMIT_PER_MINUTE', 120),
            'per_hour' => env('SURVEY_CATALOG_RATE_LIMIT_PER_HOUR', 1200),
        ],
    ],

];
