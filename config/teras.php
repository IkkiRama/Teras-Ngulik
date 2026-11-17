<?php

return [
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'), 'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'production' => (bool) env('MIDTRANS_PRODUCTION', false),
    ],
    'demo' => env('APP_DEMO', false),
];
