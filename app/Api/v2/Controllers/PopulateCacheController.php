<?php

namespace App\Api\v2\Controllers;

use App\Api\v2\Models\StationHinvModel;
use App\Api\v2\Requests\PopulateCacheRequest;
use App\Api\v2\Traits\FindAndRetrieveStationXMLTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class PopulateCacheController extends Controller
{
    use FindAndRetrieveStationXMLTrait;

    public function query(PopulateCacheRequest $request)
    {
        Log::debug('START - '.__CLASS__.' -> '.__FUNCTION__);

        /* From GET, process only '$parameters_permitted' */
        $requestOnly = $request->validated();

        /* Set Url params */
        $urlParams = 'level=channel&format=xml&starttime='.now()->format('Y-m-d');
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
            $urlParams .= '&cha=*';
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
        $url = 'http://webservices.ingv.it/fdsnws/station/1/query?'.$urlParams;
        $urlOutput = FindAndRetrieveStationXMLTrait::retrieveUrl($url);
        $urlOutputData = $urlOutput['data'];
        $urlOutputHttpStatusCode = $urlOutput['httpStatusCode'];
        Log::debug(' urlOutputHttpStatusCode='.$urlOutputHttpStatusCode);
        //Log::debug(' urlOutputData='.$urlOutputData);
        if ($urlOutputHttpStatusCode != 200) {
            abort(500, 'Error retrieving data!');
        }

        $stationXMLObj = simplexml_load_string($urlOutputData);

        $textHyp2000Stations = '';
        $channelPermittedArray = [
            'HH',
            'EH',
            'HN',
        ];
        foreach ($stationXMLObj->Network as $network) {
            $netCode = (string) $network->attributes()->code;
            foreach ($network->Station as $station) {
                $staCode = (string) $station->attributes()->code;
                foreach ($station->Channel as $channel) {
                    $chaCode = (string) $channel->attributes()->code;
                    if (in_array(substr($chaCode, 0, 2), $channelPermittedArray)) {
                        Log::debug('***** SCNL='.$netCode.'.'.$staCode.'.'.$chaCode.' ***** ');
                        $stationLine = StationHinvModel::getData([
                            'net' => $netCode,
                            'sta' => $staCode,
                            'cha' => $chaCode,
                            'cache' => $cache,
                        ], config('apollo.cacheTimeout'));
                        $textHyp2000Stations .= $stationLine;
                        /*
                        FindAndRetrieveStationXMLTrait::get([
                            'net' => $netCode,
                            'sta' => $staCode,
                            'cha' => $chaCode,
                        ], config('apollo.cacheTimeout'));
                        */
                    }
                }
            }
        }
        /* set headers */
        $headers['Content-type'] = 'text/plain';

        Log::debug('END - '.__CLASS__.' -> '.__FUNCTION__);

        return response()->make($textHyp2000Stations, 200, $headers);
    }
}
