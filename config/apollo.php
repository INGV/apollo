<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dante version
    |--------------------------------------------------------------------------
    |
    */
    'version' => trim(file_get_contents(base_path().'/VERSION')),

    /*
    |--------------------------------------------------------------------------
    | Set default path and name of log (for custom log_name, look 'config/logging.php' file and 'CustomizeFormatter' class)
    |--------------------------------------------------------------------------
    |
    */
    'log_file' => 'logs/ingv.log',

    /*
    |--------------------------------------------------------------------------
    | Horizon IP Auth
    |--------------------------------------------------------------------------
    |
    */
    'horizon_ip_auth' => explode(',', str_replace(' ', '', env('HORIZON_IP_AUTH', 'aa'))),

    /*
    |--------------------------------------------------------------------------
    | default params
    |--------------------------------------------------------------------------
    |
    */
    'default_params' => [
        'limit' => 4000,
        'format' => 'json',
        'formatted' => env('APP_DEBUG', true),
        'nodata' => 204,
    ],

    /*
    |--------------------------------------------------------------------------
    | Swagger UI Host
    |--------------------------------------------------------------------------
    */
    'swaggerUiHost' => 'http://webservices.ingv.it',

    /*
    |--------------------------------------------------------------------------
    | Route for accessing api documentation interface
    |--------------------------------------------------------------------------
    */
    'swaggerUiPath' => 'swagger-ui/dist',

    /*
    |--------------------------------------------------------------------------
    | Email recipients
    |--------------------------------------------------------------------------
    */
    'emailRecipients' => array_map('trim', explode(',', env('MAIL_RECIPIENTS', ''))), 'valentino.lauciani@ingv.it',
    'emailToFromEventdbToSeisev' => array_map('trim', explode(',', env('MAIL_TO_FROM_EVENTDB_TO_SEISEV', ''))), 'valentino.lauciani@ingv.it',

    /*
    |--------------------------------------------------------------------------
    | FDSNSWS array node; used to retrieve stations informations (now, used in 'DanteHyp2000StationsModel')
    |--------------------------------------------------------------------------
    |
    */
    /*  ********* MOVED IN THE PACKAGE: StationInv
    'fdsnws_nodes'  => explode(",", str_replace(' ', '', env('FDSNWS_NODES', 'webservices.ingv.it'))),
    */

    /*
    |--------------------------------------------------------------------------
    | Docker 'hyp2000' image name
    |--------------------------------------------------------------------------
    |
    */
    'docker_hyp2000' => env('DOCKER_HYP2000', 'hyp2000:alpine'),

    /*
    |--------------------------------------------------------------------------
    | Docker 'pyml' image name
    |--------------------------------------------------------------------------
    |
    */
    'docker_pyml' => env('DOCKER_PYML', 'pyml'),

    /*
    |--------------------------------------------------------------------------
    | Validator static parameters
    |--------------------------------------------------------------------------
    |
    */
    'validator_default_check' => [
        'net' => ['string', 'between:1,2'],
        'sta' => ['string'],
        'cha' => ['string', 'size:3'],
        'loc' => ['nullable', 'string', 'size:2'],
        'lat' => ['numeric', 'min:-90', 'max:90'],
        'lon' => ['numeric', 'min:-180', 'max:180'],
        'depth' => ['numeric', 'min:-10', 'max:10000'],
    ],

    /*
    |--------------------------------------------------------------------------
    | rfc7231 HTTP Status Code mapping
    |--------------------------------------------------------------------------
    |
    */
    'rfc7231' => [
        400 => 'https://tools.ietf.org/html/rfc7231#section-6.5.1',
        402 => 'https://tools.ietf.org/html/rfc7231#section-6.5.2',
        403 => 'https://tools.ietf.org/html/rfc7231#section-6.5.3',
        404 => 'https://tools.ietf.org/html/rfc7231#section-6.5.4',
        405 => 'https://tools.ietf.org/html/rfc7231#section-6.5.5',
        406 => 'https://tools.ietf.org/html/rfc7231#section-6.5.6',
        408 => 'https://tools.ietf.org/html/rfc7231#section-6.5.7',
        409 => 'https://tools.ietf.org/html/rfc7231#section-6.5.8',
        410 => 'https://tools.ietf.org/html/rfc7231#section-6.5.9',
        411 => 'https://tools.ietf.org/html/rfc7231#section-6.5.10',
        412 => 'https://tools.ietf.org/html/rfc4918#section-12.1',
        413 => 'https://tools.ietf.org/html/rfc7231#section-6.5.11',
        414 => 'https://tools.ietf.org/html/rfc7231#section-6.5.12',
        415 => 'https://tools.ietf.org/html/rfc7231#section-6.5.13',
        417 => 'https://tools.ietf.org/html/rfc7231#section-6.5.14',
        422 => 'https://tools.ietf.org/html/rfc4918#section-11.2',
        423 => 'https://tools.ietf.org/html/rfc4918#section-11.3',
        424 => 'https://tools.ietf.org/html/rfc4918#section-11.4',
        426 => 'https://tools.ietf.org/html/rfc7231#section-6.5.15',
        500 => 'https://tools.ietf.org/html/rfc7231#section-6.6.1',
        501 => 'https://tools.ietf.org/html/rfc7231#section-6.6.2',
        502 => 'https://tools.ietf.org/html/rfc7231#section-6.6.3',
        503 => 'https://tools.ietf.org/html/rfc7231#section-6.6.4',
        504 => 'https://tools.ietf.org/html/rfc7231#section-6.6.5',
        505 => 'https://tools.ietf.org/html/rfc7231#section-6.6.6',
        507 => 'https://tools.ietf.org/html/rfc4918#section-11.5',
    ],

    /*
    |--------------------------------------------------------------------------
    | FDSNSWS array node; used to retrieve stations informations
    |--------------------------------------------------------------------------
    |
    */
    'fdsnws_nodes' => explode(',', str_replace(' ', '', env('FDSNWS_NODES', 'webservices.ingv.it'))),

    /*
    |--------------------------------------------------------------------------
    | Enable or disable request URL cache and timeout in seconds
    |--------------------------------------------------------------------------
    |
    */
    'cacheEnabled' => env('CACHE_ENABLED', 0),
    'cacheTimeout' => env('CACHE_TIMEOUT', 2800),
];
