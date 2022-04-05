<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/

/*** V1 ***/
Route::group([
    'as'            => 'v1.',
    'middleware'    => [
        'App\Api\Middleware\JsonApiMiddleware',
        //'throttle:68,1'
    ]
], function () {
    Route::group([
        'prefix'        => 'hyp2000/v1',
        'namespace'     => 'App\Api\v1\Controllers',
        'middleware'    => [
            'throttle:100,1'
        ]
    ], function () {
        Route::post('/location', 'Hyp2000Controller@location')->name('hyp2000.location');
    });
});

/*** V2 ***/
Route::group([
    'as'            => 'v2.',
    'middleware'    => [
        'App\Api\Middleware\JsonApiMiddleware',
        //'throttle:68,1'
    ]
], function () {
    Route::group([
        'prefix'        => 'hyp2000/v2',
        'namespace'     => 'App\Api\v2\Controllers',
        'middleware'    => [
            'throttle:100,1'
        ]
    ], function () {
        Route::post('/location', 'Hyp2000Controller@location')->name('hyp2000.location');
        Route::get('/station',   '\Ingv\StationHinv\Controllers\Hyp2000StationsController@query')->name('hyp2000.station');
    });
});

/*** Status | http://localhost:8480/api/status ***/
Route::group([
    'prefix'        => '',
    'namespace'     => 'App\Api\Controllers',
], function () {
    Route::get('status', 'StatusController@index')->name('status.index');
});
