<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Set random string used in log file line
    |--------------------------------------------------------------------------
    |
    */
    'random_string' => config(['ingv-logging.random_string' => \Illuminate\Support\Str::random(5)]) . '' . config('ingv-logging.random_string'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */
    'channels' => [
        'ingv' => [
            'driver' => 'stack',
            'channels' => ['custom_daily'],
            'name' => 'API-v' . config('hyp2000ws.version', '_unknown'),
            'ignore_exceptions' => false,
        ],

        'custom_daily' => [
            'driver'    => 'daily',
            'tap'       => [Ingv\IngvLogging\Logging\CustomizeFormatter::class],
            'path'      => storage_path('logs/laravel__custom_daily.log'),
            'level'     => env('LOG_LEVEL', 'debug'),
            'formatter' => \Monolog\Formatter\LineFormatter::class,
            'formatter_with' => [
                'format' => "[%datetime% - " . config('ingv-logging.random_string') . "] - %channel%.%level_name%: %message% %context% %extra%\n",
                'dateFormat' => "Y-m-d H:i:s.u",
            ],
            'days'      => 31,
        ],
    ]
];
