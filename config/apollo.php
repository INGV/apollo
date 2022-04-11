<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dante version
    |--------------------------------------------------------------------------
    |
    */
    'version' => trim(file_get_contents(base_path() . '/VERSION')),

    /*
    |--------------------------------------------------------------------------
    | Set default path and name of log (for custom log_name, look 'config/logging.php' file and 'CustomizeFormatter' class)
    |--------------------------------------------------------------------------
    |
    */
    'log_file'       => 'logs/ingv.log',

    /*
    |--------------------------------------------------------------------------
    | Set WS URI
    |--------------------------------------------------------------------------
    |
    */
    // 'uri_ws_event' => 'http://osiride.int.ingv.it:9595/ingvws/event/1/query',
    // 'uri_ws_preferredOriginID' => 'http://osiride.int.ingv.it:9595/ingvws/preferredOriginId/1/query',
    // 'uri_ws_preferredMagnitudeId' => 'http://osiride.int.ingv.it:9595/ingvws/preferredMagnitudeId/1/query',
    // 'uri_ws_getEventIdFromOriginId' => 'http://osiride.int.ingv.it:9595/ingvws/getEventIdFromOriginId/1/query',
    // 'uri_ws__boundaries__get_region_name' => env('URI_WS__BOUNDARIES__GET_REGION_NAME'),

    /*
    |--------------------------------------------------------------------------
    | static params
    |--------------------------------------------------------------------------
    |
    */
    //'EARTH_RADIUS'                => '6371',
    // 'default_formatAllowed'         => ['json', 'text'],
    // 'default_orderByAllowed'        => ['id', 'modified'],
    // 'default_versionAllowed'        => [0, 1, 2, 100, 200, 501, 1000],
    // 'default_creatorAllowed'        => ['hew1_mole', 'hew2_mole'],
    // 'decimalsForCoordinate'         => 5,
    // 'decimalsForDistanceKm'         => 1,
    // 'default_degreeToKm'            => 111.1949, // 1 Degree = 111.1949 Km
    // 'default_minPopulation'         => 0,
    // 'default_minRadiusKm'           => 0,
    // 'default_maxRadiusKm'           => 800.0,
    // 'default_maxRadius'             => 180.0,
    // 'default_minDepth'              => -10,
    // 'default_maxDepth'              => 1000,
    // 'default_minMag'                => -1,
    // 'default_maxMag'                => 10,

    /*
    |--------------------------------------------------------------------------
    | default params
    |--------------------------------------------------------------------------
    |
    */
    'default_params' => [
        'limit'                 => 4000,
        'format'                => 'json',
        'formatted'             => env('APP_DEBUG', true),
        'nodata'                => 204,
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
    'emailRecipients'               => array_map('trim', explode(',', env('MAIL_RECIPIENTS'))), 'valentino.lauciani@ingv.it',
    'emailToFromEventdbToSeisev'    => array_map('trim', explode(',', env('MAIL_TO_FROM_EVENTDB_TO_SEISEV'))), 'valentino.lauciani@ingv.it',

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
    | Docker 'hyp2000' image name (now, used in 'DanteHyp2000Controller')
    |--------------------------------------------------------------------------
    |
    */
    'docker_hyp2000'  => env('DOCKER_HYP2000', 'hyp2000:alpine'),

    /*
    |--------------------------------------------------------------------------
    | Validator static parameters
    |--------------------------------------------------------------------------
    |
    */
    'validator_default_check' => [
        'net'                       => ['string', 'between:1,2'],
        'sta'                       => ['string'],
        'cha'                       => ['string', 'size:3'],
        'loc'                       => ['nullable', 'string'],
        'lat'                       => ['numeric', 'min:-90', 'max:90'],
        'lon'                       => ['numeric', 'min:-180', 'max:180'],
        'depth'                     => ['numeric', 'min:-10', 'max:10000'],
    ],


    /*
    |--------------------------------------------------------------------------
    | rfc7231 HTTP Status Code mapping
    |--------------------------------------------------------------------------
    |
    */
    'rfc7231'  => [
        400   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.1',
        402   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.2',
        403   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.3',
        404   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.4',
        405   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.5',
        406   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.6',
        408   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.7',
        409   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.8',
        410   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.9',
        411   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.10',
        412   =>  'https://tools.ietf.org/html/rfc4918#section-12.1',
        413   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.11',
        414   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.12',
        415   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.13',
        417   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.14',
        422   =>  'https://tools.ietf.org/html/rfc4918#section-11.2',
        423   =>  'https://tools.ietf.org/html/rfc4918#section-11.3',
        424   =>  'https://tools.ietf.org/html/rfc4918#section-11.4',
        426   =>  'https://tools.ietf.org/html/rfc7231#section-6.5.15',
        500   =>  'https://tools.ietf.org/html/rfc7231#section-6.6.1',
        501   =>  'https://tools.ietf.org/html/rfc7231#section-6.6.2',
        502   =>  'https://tools.ietf.org/html/rfc7231#section-6.6.3',
        503   =>  'https://tools.ietf.org/html/rfc7231#section-6.6.4',
        504   =>  'https://tools.ietf.org/html/rfc7231#section-6.6.5',
        505   =>  'https://tools.ietf.org/html/rfc7231#section-6.6.6',
        507   =>  'https://tools.ietf.org/html/rfc4918#section-11.5',
    ],
];
