<?php

namespace App\Api\v2\Traits;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

trait FindAndRetrieveStationXMLTrait
{
    public static function get($input_parameters, $timeoutSeconds = 2880)
    {
        Log::debug(' START - '.__CLASS__.' -> '.__FUNCTION__);

        /* Get all FDSNWS nodes */
        $fdsnws_nodes = config('apollo.fdsnws_nodes');

        /* Set Url params */
        $urlParams = 'level=channel&net='.$input_parameters['net'].'&sta='.$input_parameters['sta'].'&cha='.$input_parameters['cha'];
        if (isset($input_parameters['loc'])) {
            $urlParams .= '&loc='.$input_parameters['loc'];
        }
        if (isset($input_parameters['starttime'])) {
            $urlParams .= '&starttime='.$input_parameters['starttime'];
        } else {
            $urlParams .= '&starttime='.now()->format('Y-m-d').'T00:00:00';
        }
        if (isset($input_parameters['endtime'])) {
            $urlParams .= '&endtime='.$input_parameters['endtime'];
        } else {
            $urlParams .= '&endtime='.now()->format('Y-m-d').'T23:59:59';
        }
        if (isset($input_parameters['format'])) {
            $urlParams .= '&format='.$input_parameters['format'];
        }
        if (isset($input_parameters['cache'])) {
            $cache = $input_parameters['cache'];
        } else {
            $cache = 'true';
        }

        $func_execute_request_url = function () use ($fdsnws_nodes, $urlParams) {
            /* If 'net=IT' set directly internal FDSNWS-StationXML node */
            if (str_contains($urlParams, 'net=IT')) {
                array_unshift($fdsnws_nodes, 'exist-dev.int.ingv.it:8083');
            }

            foreach ($fdsnws_nodes as $fdsnws_node) {
                $url = 'http://'.$fdsnws_node.'/fdsnws/station/1/query?'.$urlParams;

                /* Retrieve StationXML */
                $urlOutput = self::retrieveUrl($url);

                $urlOutputData = $urlOutput['data'];
                $urlOutputHttpStatusCode = $urlOutput['httpStatusCode'];
                Log::debug(' urlOutputHttpStatusCode='.$urlOutputHttpStatusCode);

                if ($urlOutputHttpStatusCode == 200) {
                    return $urlOutputData;
                }
            }

            return '--';
        };

        $func_execute_request_url_parallel = function () use ($fdsnws_nodes, $urlParams) {
            /* If 'net=IT' set directly internal FDSNWS-StationXML node */
            if (str_contains($urlParams, 'net=IT')) {
                array_unshift($fdsnws_nodes, 'exist-dev.int.ingv.it:8083');
            }

            /* === START - First, run sync request on fdsnws_nodes that contains 'ingv.it' === */
            /* Get keys of values that contains 'ingv.it' */
            $fdsnws_nodes_filtered_keys = array_keys(array_filter($fdsnws_nodes, function ($value) {
                return strpos($value, 'ingv.it') !== false;
            }));
            if (! empty($fdsnws_nodes_filtered_keys)) {
                foreach ($fdsnws_nodes_filtered_keys as $fdsnws_nodes_filtered_key) {
                    $url = 'http://'.$fdsnws_nodes[$fdsnws_nodes_filtered_key].'/fdsnws/station/1/query?'.$urlParams;

                    /* Retrieve StationXML */
                    $urlOutput = self::retrieveUrl($url);

                    $urlOutputData = $urlOutput['data'];
                    $urlOutputHttpStatusCode = $urlOutput['httpStatusCode'];
                    Log::debug(' urlOutputHttpStatusCode='.$urlOutputHttpStatusCode);

                    if ($urlOutputHttpStatusCode == 200) {
                        return $urlOutputData;
                    }
                    unset($fdsnws_nodes[$fdsnws_nodes_filtered_key]);
                }
            }
            /* === END - First, run sync request on fdsnws_nodes that contains 'ingv.it' === */

            /* === START - Second, run async/parallel request on all fdsnws_nodes  === */
            $responses = Http::pool(
                fn (Pool $pool) => array_map(
                    function ($fdsnws_node) use ($pool, $urlParams) {
                        $pool->as($fdsnws_node)->get('http://'.$fdsnws_node.'/fdsnws/station/1/query?'.$urlParams);
                        Log::debug('  '.$fdsnws_node);
                    },
                    $fdsnws_nodes
                )
            );
            $c = 1;
            if (! empty($responses)) {
                foreach ($responses as $response) {
                    Log::debug(' ### Processing Http:pool() response: '.$c.'/'.count($responses));
                    try {
                        Log::debug('   step_0');
                        if ($response instanceof Throwable) {
                            Log::debug('   step_exception');
                            throw $response;
                        }
                        Log::debug('   step_1a: '.$response->effectiveUri()->__toString());
                        /* https://laravel.com/docs/8.x/http-client */
                        //$response = Http::timeout(5)->get($url);
                        $responseStatus = $response->status();

                        Log::debug('   step_2');
                        $response->throw();

                        Log::debug('   step_3');
                        if ($responseStatus == 200) {
                            Log::debug('   step_4a - httpStatusCode='.$responseStatus);

                            return $response->body();
                        } else {
                            Log::debug('   step_4b - httpStatusCode='.$responseStatus);
                        }
                        //$outputHttpStatusCode = $responseStatus;
                    } catch (\Illuminate\Http\Client\RequestException $e) {
                        Log::debug('   step_1b');
                        Log::debug('    getCode:'.$e->getCode());
                        Log::debug('    getMessage:'.$e->getMessage());
                        //$outputHttpStatusCode = $e->getCode();
                    } catch (\Illuminate\Http\Client\ConnectionException $e) {
                        Log::debug('   step_1c');
                        Log::debug('    getCode:'.$e->getCode());
                        Log::debug('    getMessage:'.$e->getMessage());
                        //$outputHttpStatusCode = $e->getCode();
                    } catch (\Exception $e) {
                        Log::debug('   step_1d');
                        Log::debug('    getCode:'.$e->getCode());
                        Log::debug('    getMessage:'.$e->getMessage());
                    }

                    $c++;
                }
            }
            /* === END - Second, run async request on all fdsnws_nodes  === */

            return '--';
        };

        /* START - Set Redis chache key */
        $redisCacheKey = 'stationxml';
        if (isset($input_parameters['format']) && ! empty($input_parameters['format'])) {
            $redisCacheKey .= $input_parameters['format'];
        }
        $redisCacheKey .= '__'.$input_parameters['net'].'.'.$input_parameters['sta'];
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

        if (config('apollo.cacheEnabled')) {
            Log::debug('  Query cache enabled (timeout='.$timeoutSeconds.'sec), redisCacheKey="'.$redisCacheKey.'"');
            if ($cache == 'false') {
                Log::debug('   GET request contains \'cache=false\', forget cache');
                Cache::forget($redisCacheKey);
            }
            $stationXML = Cache::remember($redisCacheKey, $timeoutSeconds, $func_execute_request_url_parallel);
        } else {
            Log::debug('  Query cache NOT enabled');
            if (Cache::has($redisCacheKey)) {
                Log::debug('   forget:'.$redisCacheKey);
                Cache::forget($redisCacheKey);
            }
            $stationXML = $func_execute_request_url_parallel();
        }

        if ($stationXML == '--') {
            $textMessage = '!ATTENTION! - StationXML data not found: "'.$redisCacheKey.'"';
            if (config('apollo.cacheEnabled')) {
                if (Cache::has($redisCacheKey)) {
                    $redisCacheKeyForNotFounded = 'notFounded__'.$redisCacheKey;
                    if (Cache::has($redisCacheKeyForNotFounded)) {
                        $textMessage .= ' cache key timeout was already set to 86400sec (24h) instead of '.config('apollo.cacheTimeout').'sec. Nothing to do...';
                    } else {
                        $textMessage .= ' cache key timeout will be changed to 86400sec (24h) instead of '.config('apollo.cacheTimeout').'sec.';
                        Cache::put($redisCacheKey, $stationXML, 86400);
                        Cache::add($redisCacheKeyForNotFounded, '', 86401);
                    }
                }
            }
            Log::debug('   '.$textMessage);
        }

        Log::debug(' END - '.__CLASS__.' -> '.__FUNCTION__);
        if ($stationXML == '--') {
            return null;
        } else {
            return $stationXML;
        }
    }

    public static function retrieveUrl($url)
    {
        Log::debug('  START - '.__CLASS__.' -> '.__FUNCTION__);

        /* Set variables */
        $outputData = '';
        $outputHttpStatusCode = 404;

        try {
            Log::debug('   step_1a: '.$url);
            /* https://laravel.com/docs/8.x/http-client */
            $response = Http::timeout(10)->get($url);
            //dd($response->effectiveUri()->__toString());
            $responseStatus = $response->status();

            Log::debug('   step_2');
            $response->throw();

            Log::debug('   step_3');
            if ($responseStatus == 200) {
                Log::debug('   step_4a - httpStatusCode='.$responseStatus);
                $outputData = $response->body();
            } else {
                Log::debug('   step_4b - httpStatusCode='.$responseStatus);
            }
            $outputHttpStatusCode = $responseStatus;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::debug('   step_1b');
            Log::debug('    getCode:'.$e->getCode());
            Log::debug('    getMessage:'.$e->getMessage());
            $outputHttpStatusCode = $e->getCode();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::debug('   step_1c');
            Log::debug('    getCode:'.$e->getCode());
            Log::debug('    getMessage:'.$e->getMessage());
            $outputHttpStatusCode = $e->getCode();
        } catch (\Exception $e) {
            Log::debug('   step_1d');
            Log::debug('    getCode:'.$e->getCode());
            Log::debug('    getMessage:'.$e->getMessage());
        }

        Log::debug('  END - '.__CLASS__.' -> '.__FUNCTION__);

        return $outputData = [
            'data' => $outputData,
            'httpStatusCode' => $outputHttpStatusCode,
        ];
    }
}
