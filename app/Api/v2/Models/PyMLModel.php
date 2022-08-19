<?php

namespace App\Api\v2\Models;

use App\Api\v2\Traits\FindAndRetrieveStationXMLTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PyMLModel extends Model
{
    use FindAndRetrieveStationXMLTrait;

    public static function getCoord($input_parameters, $timeoutSeconds = 2880, $logString = '')
    {
        Log::debug($logString.'START - '.__CLASS__.' -> '.__FUNCTION__);

        // Add 'format=text'
        $input_parameters['format'] = 'text';

        // Check 'starttime' and 'endtime'
        /*
        if (isset($input_parameters['time1']) && ! empty($input_parameters['time1'])) {
            $input_parameters['starttime'] = substr($input_parameters['time1'], 0, 10).'T00:00:00';
            $input_parameters['endtime'] = substr($input_parameters['time1'], 0, 10).'T23:59:59';
        } elseif (isset($input_parameters['time2']) && ! empty($input_parameters['time2'])) {
            $input_parameters['starttime'] = substr($input_parameters['time2'], 0, 10).'T00:00:00';
            $input_parameters['endtime'] = substr($input_parameters['time2'], 0, 10).'T23:59:59';
        } else {
            $input_parameters['starttime'] = now()->format('Y-m-d').'T00:00:00';
            $input_parameters['endtime'] = now()->format('Y-m-d').'T23:59:59';
        }
        */

        // Closure for executing a request url
        $func_execute_request_url = function () use ($input_parameters, $timeoutSeconds) {
            $stationXMLText = FindAndRetrieveStationXMLTrait::get($input_parameters, $timeoutSeconds);

            if (is_null($stationXMLText)) {
                $toReturn = [];
            } else {
                $stationXMLText = explode("\n", $stationXMLText);
                unset($stationXMLText[0]); // remove comment line

                $stationXMLTextExploded = explode('|', $stationXMLText[1]);

                $net = (string) $stationXMLTextExploded[0];
                $sta = (string) $stationXMLTextExploded[1];
                $cha = (string) $stationXMLTextExploded[3];
                $loc = (string) $stationXMLTextExploded[2];
                $lat = (float) $stationXMLTextExploded[4];
                $lon = (float) $stationXMLTextExploded[5];
                $elev = (float) $stationXMLTextExploded[6];

                $toReturn = [
                    'lat' => $lat,
                    'lon' => $lon,
                    'elev' => $elev,
                ];
            }

            return $toReturn;
        };

        /* START - Set Redis chache key */
        $redisCacheKey = 'pyml_coord__'.$input_parameters['net'].'.'.$input_parameters['sta'];
        if (isset($input_parameters['loc']) && ! empty($input_parameters['loc'])) {
            $redisCacheKey .= '.'.$input_parameters['loc'];
        } else {
            $redisCacheKey .= '.--';
        }
        $redisCacheKey .= '.'.$input_parameters['cha'];
        /*
        if (isset($input_parameters['starttime']) && ! empty($input_parameters['starttime'])) {
            $redisCacheKey .= '__'.str_replace('-', '', substr($input_parameters['starttime'], 0, 7));
        }
        if (isset($input_parameters['endtime']) && ! empty($input_parameters['endtime'])) {
            $redisCacheKey .= '-'.str_replace('-', '', substr($input_parameters['endtime'], 0, 7));
        }
        */
        /* END - Set Redis chache key */

        /* Set $cache */
        if (isset($input_parameters['cache'])) {
            $cache = $input_parameters['cache'];
        } else {
            $cache = 'true';
        }

        if (config('apollo.cacheEnabled')) {
            Log::debug(' Query cache enabled (timeout='.$timeoutSeconds.'sec), redisCacheKey="'.$redisCacheKey.'"');
            if ($cache == 'false') {
                Log::debug('  GET request contains \'cache=false\', forget cache');
                Cache::forget($redisCacheKey);
            }
            $pyMLCoordArray = Cache::remember($redisCacheKey, $timeoutSeconds, $func_execute_request_url);
        } else {
            Log::debug(' Query cache NOT enabled');
            if (Cache::has($redisCacheKey)) {
                Log::debug('  forget:'.$redisCacheKey);
                Cache::forget($redisCacheKey);
            }
            $pyMLCoordArray = $func_execute_request_url();
        }
        if (empty($pyMLCoordArray)) {
            $textMessage = '!ATTENTION! - Not found: "'.$redisCacheKey.'"';
            if (config('apollo.cacheEnabled')) {
                if (Cache::has($redisCacheKey)) {
                    $textMessage .= ' change cache timeout to 86400sec (24h).';
                    Cache::put($redisCacheKey, $pyMLCoordArray, 86400);
                }
            }
            Log::debug('  '.$textMessage);
        }

        Log::debug(' Output: pyMLCoordArray="', $pyMLCoordArray);
        Log::debug($logString.'END - '.__CLASS__.' -> '.__FUNCTION__);
        if (empty($pyMLCoordArray)) {
            return [];
        } else {
            return $pyMLCoordArray;
        }
    }
}
