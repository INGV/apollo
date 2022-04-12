<?php

namespace App\Api\v2\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use App\Api\v2\Traits\FindAndRetrieveStationXMLTrait;
use VLauciani\FortranFormatter\Traits\FortranFormatTrait;

class StationHinvModel extends Model
{
    use FortranFormatTrait, FindAndRetrieveStationXMLTrait;

    public static function DECtoDMS($latitude, $longitude)
    {
        $latitudeDirection = $latitude < 0 ? 'S' : 'N';
        $longitudeDirection = $longitude < 0 ? 'W' : 'E';

        $latitudeNotation = $latitude < 0 ? '-' : '';
        $longitudeNotation = $longitude < 0 ? '-' : '';

        $latitudeInDegrees = floor(abs($latitude));
        $longitudeInDegrees = floor(abs($longitude));

        $latitudeDecimal = abs($latitude) - $latitudeInDegrees;
        $longitudeDecimal = abs($longitude) - $longitudeInDegrees;

        $_precision = 4;
        $latitudeMinutes = round($latitudeDecimal * 60, $_precision);
        $longitudeMinutes = round($longitudeDecimal * 60, $_precision);

        return [
            'lat' => [
                'notation'    => $latitudeNotation,
                'degrees'    => $latitudeInDegrees,
                'minutes'    => $latitudeMinutes,
                'direction'    => $latitudeDirection,
            ],
            'lon' => [
                'notation'    => $longitudeNotation,
                'degrees'    => $longitudeInDegrees,
                'minutes'    => $longitudeMinutes,
                'direction'    => $longitudeDirection,
            ]
        ];
        /*
		return sprintf('%s%s %s%s %s%s %s%s',
			$latitudeNotation,
			$latitudeInDegrees,
			$latitudeMinutes,
			$latitudeDirection,
			$longitudeNotation,
			$longitudeInDegrees,
			$longitudeMinutes,
			$longitudeDirection
		);
		*/
    }

    public static function getData($input_parameters, $timeoutSeconds = 2880)
    {
        Log::debug("START - " . __CLASS__ . ' -> ' . __FUNCTION__);

        // Closure for executing a request url
        $func_execute_request_url = function () use ($input_parameters, $timeoutSeconds) {
            $stationXML = FindAndRetrieveStationXMLTrait::get($input_parameters, $timeoutSeconds);

            if (is_null($stationXML)) {
                $text = '--';
            } else {
                $stationXMLObj = simplexml_load_string($stationXML);

                $text = '';
                $str_pad_string = ' ';
                foreach ($stationXMLObj->Network as $network) {
                    $net                    = (string) $network->attributes()->code;
                    foreach ($network->Station as $station) {
                        $sta                = (string) $station->attributes()->code;
                        foreach ($station->Channel as $channel) {
                            $cha            = (string) $channel->attributes()->code;
                            $loc            = (string) $channel->attributes()->locationCode;
                            $lat            = (float) $channel->Latitude;
                            $lon            = (float) $channel->Longitude;
                            $elev           = (float) $channel->Elevation;

                            /* Convert 'lat' and 'lon' */
                            $arrayDmsLatLon = self::DECtoDMS($lat, $lon);

                            /* Format data */
                            $staFormatted       = self::fromFortranFormatToString('A5',   $sta, $str_pad_string, STR_PAD_RIGHT);
                            $netFormatted       = self::fromFortranFormatToString('A2',   $net, $str_pad_string);
                            $chaCompFormatted   = self::fromFortranFormatToString('A1',   substr($cha, 2, 1), $str_pad_string);
                            $chaFormatted       = self::fromFortranFormatToString('A3',   $cha, $str_pad_string);
                            $blank              = self::fromFortranFormatToString('1X',   null, $str_pad_string);
                            $latDegFormatted    = self::fromFortranFormatToString('I2',   $arrayDmsLatLon['lat']['degrees'], $str_pad_string);
                            $latMinFormatted    = self::fromFortranFormatToString('F7.4', $arrayDmsLatLon['lat']['minutes'], $str_pad_string);
                            $latDirFormatted    = self::fromFortranFormatToString('A1',    $arrayDmsLatLon['lat']['direction'], $str_pad_string);
                            $lonDegFormatted    = self::fromFortranFormatToString('I3',   $arrayDmsLatLon['lon']['degrees'], $str_pad_string);
                            $lonMinFormatted    = self::fromFortranFormatToString('F7.4', $arrayDmsLatLon['lon']['minutes'], $str_pad_string);
                            $lonDirFormatted    = self::fromFortranFormatToString('A1',   $arrayDmsLatLon['lon']['direction'], $str_pad_string);
                            $elevFormatted      = self::fromFortranFormatToString('I4',   explode(".", $elev)[0], $str_pad_string);

                            $arrayKey = $net . '_' . $sta . '_' . $cha . '_' . $loc;
                            $text =
                                $staFormatted .
                                $blank .
                                $netFormatted .
                                $blank .
                                $chaCompFormatted .
                                $chaFormatted .
                                $blank .
                                $blank .
                                $latDegFormatted .
                                $blank .
                                $latMinFormatted .
                                $latDirFormatted .
                                $lonDegFormatted .
                                $blank .
                                $lonMinFormatted .
                                $lonDirFormatted .
                                $elevFormatted .
                                $blank .
                                $blank .
                                $blank .
                                $blank .
                                $blank .
                                "1" .
                                $blank .
                                $blank .
                                "0.00" .
                                $blank .
                                $blank .
                                "0.00" .
                                $blank .
                                $blank .
                                "0.00" .
                                $blank .
                                $blank .
                                "0.00" .
                                $blank .
                                "0" .
                                $blank .
                                $blank .
                                "1.00--" .
                                "\n";
                        }
                    }
                }
            }
            return $text;
        };

        /* START - Set Redis chache key */
        $redisCacheKey = 'station-hinv__' . $input_parameters['net'] . '.' . $input_parameters['sta'];
        if (isset($input_parameters['loc'])) {
            $redisCacheKey .= '.' . $input_parameters['loc'];
        }
        $redisCacheKey .= '.' . $input_parameters['cha'];
        //$redisCacheKey .= '_' . $fdsnws_node;
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
            $hyp2000StationLine = Cache::remember($redisCacheKey, $timeoutSeconds, $func_execute_request_url);
        } else {
            Log::debug(' Query cache NOT enabled');
            if (Cache::has($redisCacheKey)) {
                Log::debug('  forget:' . $redisCacheKey);
                Cache::forget($redisCacheKey);
            }
            $hyp2000StationLine = $func_execute_request_url();
        }
        if ($hyp2000StationLine == '--') {
            $textMessage = '!ATTENTION! - Not found: "' . $redisCacheKey . '"';
            if (config('apollo.cacheEnabled')) {
                if (Cache::has($redisCacheKey)) {
                    $textMessage .= ' change cache timeout to 86400sec (24h).';
                    Cache::put($redisCacheKey, $hyp2000StationLine, 86400);
                }
            }
            Log::debug('  ' . $textMessage);
        }

        Log::debug(' Output: hyp2000StationLine="' . $hyp2000StationLine . '"');
        Log::debug("END - " . __CLASS__ . ' -> ' . __FUNCTION__);
        if ($hyp2000StationLine == '--') {
            return null;
        } else {
            return $hyp2000StationLine;
        }
    }
}
