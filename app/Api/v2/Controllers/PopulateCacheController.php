<?php

namespace App\Api\v2\Controllers;

use App\Api\v2\Models\PyMLModel;
use App\Api\v2\Models\StationHinvModel;
use App\Api\v2\Requests\PopulateCacheRequest;
use App\Api\v2\Traits\FindAndRetrieveStationXMLTrait;
use App\Http\Controllers\Controller;
use Async;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PopulateCacheController extends Controller
{
    use FindAndRetrieveStationXMLTrait;

    public function query(PopulateCacheRequest $request)
    {
        Log::info('START - ' . __CLASS__ . ' -> ' . __FUNCTION__);
        $queryTimeStart = microtime(true);

        /* From GET, process only '$parameters_permitted' */
        $requestOnly = $request->validated();

        /* Set Url params */
        $urlParams = 'authoritative=any&level=channel&format=text&starttime=' . now()->format('Y-m-d');
        if (isset($requestOnly['net'])) {
            $urlParams .= '&net=' . $requestOnly['net'];
        } else {
            $urlParams .= '&net=IV';
        }
        if (isset($requestOnly['sta'])) {
            $urlParams .= '&sta=' . $requestOnly['sta'];
        } else {
            $urlParams .= '&sta=*';
        }
        if (isset($requestOnly['cha'])) {
            $urlParams .= '&cha=' . $requestOnly['cha'];
        } else {
            $urlParams .= '&cha=HH?,EH?,HN?';
        }
        if (isset($requestOnly['cache'])) {
            $cache = $requestOnly['cache'];
        } else {
            $cache = 'true';
        }

        /*
        $stationXML = FindAndRetrieveStationXMLTrait::get([
            'net' => 'IV',
            'sta' => 'ACER',
            'cha' => 'HHZ',
        ], 2);
        */

        /* */
        $url = 'http://webservices.ingv.it/fdsnws/station/1/query?' . $urlParams;
        $urlOutput = FindAndRetrieveStationXMLTrait::retrieveUrl($url);
        $urlOutputData = $urlOutput['data'];
        $urlOutputHttpStatusCode = $urlOutput['httpStatusCode'];
        Log::debug(' urlOutputHttpStatusCode=' . $urlOutputHttpStatusCode);
        if ($urlOutputHttpStatusCode != 200) {
            abort($urlOutputHttpStatusCode);
        }

        // Prepare data
        $arrayScnls = [];
        $urlOutputData = explode("\n", $urlOutputData);
        unset($urlOutputData[0]);
        foreach ($urlOutputData as $line) {
            $b = explode('|', $line);
            if (!empty($b[3])) {
                $arrayScnls[] = [
                    'net' => $b[0],
                    'sta' => $b[1],
                    'cha' => $b[3],
                ];
            }
        }

        // Process data
        $count = 1;
        $textHyp2000Stations = '';
        foreach ($arrayScnls as $arrayScnl) {
            Log::debug('***** ' . $count . '/' . count($arrayScnls) . ' - SCNL=' . $arrayScnl['net'] . '.' . $arrayScnl['sta'] . '.' . $arrayScnl['cha'] . ' ***** ');
            $textHyp2000Stations .= $arrayScnl['net'] . '.' . $arrayScnl['sta'] . '.' . $arrayScnl['cha'] . "\n";

            // ===== 1 =====
            //FindAndRetrieveStationXMLTrait::get([
            //    'net' => $arrayScnl['net'],
            //    'sta' => $arrayScnl['sta'],
            //    'cha' => $arrayScnl['cha'],
            //    'format' => 'text',
            //    'cache' => $cache,
            //], config('apollo.cacheTimeout'));

            // ===== 2 =====
            //$promises[] = 'http://webservices.ingv.it/fdsnws/station/1/query?net='.$arrayScnl['net'].'&sta='.$arrayScnl['sta'].'&cha='.$arrayScnl['cha'];

            /*
            // ===== 3 =====
            Async::run(function () use ($arrayScnl) {
                /*
                Async::run(function () use ($netCode, $staCode, $chaCode, $cache) {
                    Log::debug('==== Async ====');
                    \App\Api\v2\Models\StationHinvModel::getData([
                        'net' => $netCode,
                        'sta' => $staCode,
                        'cha' => $chaCode,
                        'cache' => $cache,
                    ], config('apollo.cacheTimeout'));

                    return 1;
                });
                */

            // ===== 4 =====
            StationHinvModel::getData([
                'net' => $arrayScnl['net'],
                'sta' => $arrayScnl['sta'],
                'cha' => $arrayScnl['cha'],
                'cache' => $cache,
            ], config('apollo.cacheTimeout'));

            PyMLModel::getCoord([
                'net' => $arrayScnl['net'],
                'sta' => $arrayScnl['sta'],
                'cha' => $arrayScnl['cha'],
                'cache' => $cache,
            ], config('apollo.cacheTimeout'));

            /*
                // ===== 5 =====
                FindAndRetrieveStationXMLTrait::get([
                    'net' => $arrayScnl['net'],
                    'sta' => $arrayScnl['sta'],
                    'cha' => $arrayScnl['cha'],
                    'cache' => 'false',
                ], config('apollo.cacheTimeout'));

                return $arrayScnl['net'].'.'.$arrayScnl['sta'].'.'.$arrayScnl['cha'];
            });
            */
            $count++;
        }
        // ===== 2 =====
        /*
        $responses = Http::pool(function (Pool $pool) use ($promises) {
            return collect($promises)
                ->map(fn ($line) => $pool->get($line));
        });
        $queryExecutionTime = number_format((microtime(true) - $queryTimeStart) * 1000, 2);
        Log::info('END - '.__CLASS__.' -> '.__FUNCTION__.' | queryExecutionTime='.$queryExecutionTime.' Milliseconds');
        dd($promises, $responses, $responses[0]->ok(), $responses[0]->getBody()->getContents());
        */

        /* set headers */
        $headers['Content-type'] = 'text/plain';

        Log::debug(' STA_NOT_FOUNDED:' . config('apollo.stations_not_founded'));
        $queryExecutionTime = number_format((microtime(true) - $queryTimeStart) * 1000, 2);
        Log::info('END - ' . __CLASS__ . ' -> ' . __FUNCTION__ . ' | queryExecutionTime=' . $queryExecutionTime . ' Milliseconds');

        return response()->make($textHyp2000Stations, 200, $headers);
    }
}
