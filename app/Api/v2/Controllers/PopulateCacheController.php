<?php

namespace App\Api\v2\Controllers;

use App\Api\v2\Jobs\PyMLJob;
use App\Api\v2\Jobs\StationHinvJob;
use App\Api\v2\Requests\PopulateCacheRequest;
use App\Api\v2\Traits\FindAndRetrieveStationXMLTrait;
use App\Http\Controllers\Controller;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class PopulateCacheController extends Controller
{
    use FindAndRetrieveStationXMLTrait;

    public function query(PopulateCacheRequest $request)
    {
        Log::info('START - '.__CLASS__.' -> '.__FUNCTION__);
        $queryTimeStart = microtime(true);

        /* From GET, process only '$parameters_permitted' */
        $requestOnly = $request->validated();

        /* Set Url params */
        $urlParams = 'level=channel&format=text&starttime='.now()->format('Y-m-d');
        if (isset($requestOnly['net'])) {
            $urlParams .= '&net='.$requestOnly['net'];
        } else {
            $urlParams .= '&net=IV';
        }
        if (isset($requestOnly['sta'])) {
            $urlParams .= '&sta='.$requestOnly['sta'];
        } else {
            $urlParams .= '&sta=*';
        }
        if (isset($requestOnly['cha'])) {
            $urlParams .= '&cha='.$requestOnly['cha'];
        } else {
            $urlParams .= '&cha=HH?,EH?,HN?';
        }
        if (isset($requestOnly['cache'])) {
            $cache = $requestOnly['cache'];
        } else {
            $cache = 'true';
        }

        /* */
        $url = 'http://webservices.ingv.it/fdsnws/station/1/query?'.$urlParams;
        $urlOutput = FindAndRetrieveStationXMLTrait::retrieveUrl($url);
        $urlOutputData = $urlOutput['data'];
        $urlOutputHttpStatusCode = $urlOutput['httpStatusCode'];
        Log::debug(' urlOutputHttpStatusCode='.$urlOutputHttpStatusCode);
        if ($urlOutputHttpStatusCode != 200) {
            abort($urlOutputHttpStatusCode);
        }

        // Prepare data
        $arrayScnls = [];
        $urlOutputData = explode("\n", $urlOutputData);
        unset($urlOutputData[0]);
        foreach ($urlOutputData as $line) {
            $b = explode('|', $line);
            if (! empty($b[3])) {
                $arrayScnls[] = [
                    'net' => $b[0],
                    'sta' => $b[1],
                    'cha' => $b[3],
                ];
            }
        }

        // Process data
        $count = 1;
        $nArrayScnls = count($arrayScnls);
        $textHyp2000Stations = '';

        $batch = Bus::batch([])
            ->then(function (Batch $batch) {
                // All jobs completed successfully...
                Log::info(' then');
            })->catch(function (Batch $batch, Throwable $e) {
                // First batch job failure detected...
                Log::info(' catch:'.$e->getMessage());
            })->finally(function (Batch $batch) {
                // The batch has finished executing...
                Log::info(' finally');
            })->dispatch();

        foreach ($arrayScnls as $arrayScnl) {
            Log::debug(' Preparing Job: '.$count.'/'.$nArrayScnls.' - SCNL='.$arrayScnl['net'].'.'.$arrayScnl['sta'].'.'.$arrayScnl['cha']);
            $textHyp2000Stations .= $arrayScnl['net'].'.'.$arrayScnl['sta'].'.'.$arrayScnl['cha']."\n";

            $batch->add([
                [
                    new StationHinvJob([
                        'count' => $count,
                        'total' => $nArrayScnls,
                        'net' => $arrayScnl['net'],
                        'sta' => $arrayScnl['sta'],
                        'cha' => $arrayScnl['cha'],
                        'cache' => $cache,
                    ]),
                    new PyMLJob([
                        'count' => $count,
                        'total' => $nArrayScnls,
                        'net' => $arrayScnl['net'],
                        'sta' => $arrayScnl['sta'],
                        'cha' => $arrayScnl['cha'],
                        'cache' => $cache,
                    ]),
                ],
            ]);

            $count++;
        }

        $headers['Content-type'] = 'text/plain';
        $queryExecutionTime = number_format((microtime(true) - $queryTimeStart) * 1000, 2);
        Log::info('END - '.__CLASS__.' -> '.__FUNCTION__.' | queryExecutionTime='.$queryExecutionTime.' Milliseconds');

        return response()->make($batch->id."\n".$textHyp2000Stations, 200, $headers);
    }
}
