<?php

namespace App\Api\v2\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use App\Api\v2\Traits\FindAndRetrieveStationXMLTrait;

class PyMLModel extends Model
{
    use FindAndRetrieveStationXMLTrait;

    public static function getCoord($input_parameters, $timeoutSeconds = 2880, $logString = '')
    {
        Log::debug($logString . 'START - ' . __CLASS__ . ' -> ' . __FUNCTION__);

        // Closure for executing a request url
        $func_execute_request_url = function () use ($input_parameters, $timeoutSeconds) {
            $stationXML = FindAndRetrieveStationXMLTrait::get($input_parameters, $timeoutSeconds);

            if (is_null($stationXML)) {
                $toReturn = [];
            } else {
                $stationXMLObj = simplexml_load_string($stationXML);

                $toReturn = '';
                $str_pad_string = ' ';
                foreach ($stationXMLObj->Network as $network) {
                    $net                    = (string) $network->attributes()->code;
                    foreach ($network->Station as $station) {
                        $sta                = (string) $station->attributes()->code;
                        foreach ($station->Channel as $channel) {
                            $lat            = (float) $channel->Latitude;
                            $lon            = (float) $channel->Longitude;
                            $elev           = (float) $channel->Elevation;

                            $toReturn = [
                                'lat' => $lat,
                                'lon' => $lon,
                                'elev' => $elev,
                            ];
                        }
                    }
                }
            }
            return $toReturn;
        };

        /* START - Set Redis chache key */
        $redisCacheKey = 'pyml_coord__' . $input_parameters['net'] . '.' . $input_parameters['sta'];
        if (isset($input_parameters['loc']) && !empty($input_parameters['loc'])) {
            $redisCacheKey .= '.' . $input_parameters['loc'];
        } else {
            $redisCacheKey .= '.--';
        }
        $redisCacheKey .= '.' . $input_parameters['cha'];
        /* END - Set Redis chache key */

        /* Set $cache */
        if (isset($input_parameters['cache'])) {
            $cache = $input_parameters['cache'];
        } else {
            $cache = "true";
        }

        if (config('apollo.cacheEnabled')) {
            Log::debug(' Query cache enabled (timeout=' . $timeoutSeconds . 'sec), redisCacheKey="' . $redisCacheKey . '"');
            if ($cache == "false") {
                Log::debug('  GET request contains \'cache=false\', forget cache');
                Cache::forget($redisCacheKey);
            }
            $pyMLCoordArray = Cache::remember($redisCacheKey, $timeoutSeconds, $func_execute_request_url);
        } else {
            Log::debug(' Query cache NOT enabled');
            if (Cache::has($redisCacheKey)) {
                Log::debug('  forget:' . $redisCacheKey);
                Cache::forget($redisCacheKey);
            }
            $pyMLCoordArray = $func_execute_request_url();
        }
        if (empty($pyMLCoordArray)) {
            $textMessage = '!ATTENTION! - Not found: "' . $redisCacheKey . '"';
            if (config('apollo.cacheEnabled')) {
                if (Cache::has($redisCacheKey)) {
                    $textMessage .= ' change cache timeout to 86400sec (24h).';
                    Cache::put($redisCacheKey, $pyMLCoordArray, 86400);
                }
            }
            Log::debug('  ' . $textMessage);
        }

        Log::debug(' Output: pyMLCoordArray="', $pyMLCoordArray);
        Log::debug($logString . 'END - ' . __CLASS__ . ' -> ' . __FUNCTION__);
        if (empty($pyMLCoordArray)) {
            return [];
        } else {
            return $pyMLCoordArray;
        }
    }
}
