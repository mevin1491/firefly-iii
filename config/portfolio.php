<?php

declare(strict_types=1);

return [
    'enabled'       => env('ENABLE_PORTFOLIO_TRACKER', false),
    'sync_interval' => (int) env('PORTFOLIO_SYNC_INTERVAL', 3600),

    'moomoo' => [
        'host'      => env('MOOMOO_OPEND_HOST', '127.0.0.1'),
        'port'      => (int) env('MOOMOO_OPEND_PORT', 11111),
        'trade_env' => (int) env('MOOMOO_OPEND_TRADE_ENV', 0),
    ],

    'luno' => [
        'api_key_id'     => env('LUNO_API_KEY_ID', ''),
        'api_key_secret' => env('LUNO_API_KEY_SECRET', ''),
        'base_url'       => 'https://api.luno.com/api/1/',
    ],

    'fsmone' => [
        'date_format' => 'd/m/Y',
        'delimiter'   => ',',
    ],
];
