<?php

namespace App\Api\v2\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

trait FindAndRetrieveStationXMLTrait
{
    public static function get($input_parameters, $timeoutSeconds = 2880)
    {
        Log::debug(" START - " . __CLASS__ . ' -> ' . __FUNCTION__);

        /* Get all FDSNWS nodes */
        $fdsnws_nodes = config('apollo.fdsnws_nodes');

        /* Set Url params */
        $urlParams = 'level=channel&net=' . $input_parameters['net'] . '&sta=' . $input_parameters['sta'] . '&cha=' . $input_parameters['cha'];
        if (isset($input_parameters['loc'])) {
            $urlParams .= '&loc=' . $input_parameters['loc'];
        }
        if (isset($input_parameters['starttime'])) {
            $urlParams .= '&starttime=' . $input_parameters['starttime'];
        }
        if (isset($input_parameters['endtime'])) {
            $urlParams .= '&endtime=' . $input_parameters['endtime'];
        }
        if (isset($input_parameters['cache'])) {
            $cache = $input_parameters['cache'];
        } else {
            $cache = "true";
        }

        $func_execute_request_url = function () use ($fdsnws_nodes, $urlParams) {
            foreach ($fdsnws_nodes as $fdsnws_node) {
                $url = 'http://' . $fdsnws_node . '/fdsnws/station/1/query?' . $urlParams;

                /* Retrieve StationXML */
                $urlOutput = self::getXml($url);

                $urlOutputData              = $urlOutput['data'];
                $urlOutputHttpStatusCode    = $urlOutput['httpStatusCode'];
                Log::debug(" urlOutputHttpStatusCode=" . $urlOutputHttpStatusCode);

                if ($urlOutputHttpStatusCode == 200) {
                    return $urlOutputData;
                }
            }
            return '--';
        };

        /* START - Set Redis chache key */
        $redisCacheKey = 'stationxml__' . $input_parameters['net'] . '.' . $input_parameters['sta'];
        if (isset($input_parameters['loc']) && !empty($input_parameters['loc'])) {
            $redisCacheKey .= '.' . $input_parameters['loc'];
        }
        $redisCacheKey .= '.' . $input_parameters['cha'];
        /* END - Set Redis chache key */

        if (config('apollo.cacheEnabled')) {
            Log::debug('  Query cache enabled (timeout=' . $timeoutSeconds . 'sec), redisCacheKey="' . $redisCacheKey . '"');
            if ($cache == "false") {
                Log::debug('   GET request contains \'cache=false\', forget cache');
                Cache::forget($redisCacheKey);
            }
            $stationXML = Cache::remember($redisCacheKey, $timeoutSeconds, $func_execute_request_url);
        } else {
            Log::debug('  Query cache NOT enabled');
            if (Cache::has($redisCacheKey)) {
                Log::debug('   forget:' . $redisCacheKey);
                Cache::forget($redisCacheKey);
            }
            $stationXML = $func_execute_request_url();
        }

        if ($stationXML == '--') {
            $textMessage = '!ATTENTION! - Not found: "' . $redisCacheKey . '"';
            if (config('apollo.cacheEnabled')) {
                if (Cache::has($redisCacheKey)) {
                    $textMessage .= ' change cache timeout to 86400sec (24h).';
                    Cache::put($redisCacheKey, $stationXML, 86400);
                }
            }
            Log::debug('   ' . $textMessage);
        }

        Log::debug(" END - " . __CLASS__ . ' -> ' . __FUNCTION__);
        if ($stationXML == '--') {
            return null;
        } else {
            return $stationXML;
        }
    }

    public static function getXml($url)
    {
        Log::debug("  START - " . __CLASS__ . ' -> ' . __FUNCTION__);

        /* Set variables */
        $outputData = "";
        $outputHttpStatusCode = 404;

        try {
            Log::debug("   step_1a: " . $url);
            /* https://laravel.com/docs/8.x/http-client */
            $response = Http::timeout(10)->get($url);
            $responseStatus = $response->status();

            Log::debug("   step_2");
            $response->throw();

            Log::debug("   step_3");
            if ($responseStatus == 200) {
                Log::debug("   step_4a - httpStatusCode=" . $responseStatus);
                $outputData      = $response->body();
            } else {
                Log::debug("   step_4b - httpStatusCode=" . $responseStatus);
            }
            $outputHttpStatusCode = $responseStatus;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::debug("   step_1b");
            Log::debug("    getCode:" . $e->getCode());
            Log::debug("    getMessage:" . $e->getMessage());
            $outputHttpStatusCode = $e->getCode();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::debug("   step_1c");
            Log::debug("    getCode:" . $e->getCode());
            Log::debug("    getMessage:" . $e->getMessage());
            $outputHttpStatusCode = $e->getCode();
        } catch (\Exception $e) {
            Log::debug("   step_1d");
            Log::debug("    getCode:" . $e->getCode());
            Log::debug("    getMessage:" . $e->getMessage());
        }

        Log::debug("  END - " . __CLASS__ . ' -> ' . __FUNCTION__);
        return $outputData = [
            'data'              =>  $outputData,
            'httpStatusCode'    =>  $outputHttpStatusCode
        ];
    }
}
