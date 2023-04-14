<?php

namespace App\Api\v2\Models;

use App\Api\v2\Traits\FindAndRetrieveStationXMLTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
                'notation' => $latitudeNotation,
                'degrees' => $latitudeInDegrees,
                'minutes' => $latitudeMinutes,
                'direction' => $latitudeDirection,
            ],
            'lon' => [
                'notation' => $longitudeNotation,
                'degrees' => $longitudeInDegrees,
                'minutes' => $longitudeMinutes,
                'direction' => $longitudeDirection,
            ],
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
        Log::debug('START - ' . __CLASS__ . ' -> ' . __FUNCTION__);

        // Add 'format=text'
        $input_parameters['format'] = 'text';

        // Check 'starttime' and 'endtime'
        /*
        if (isset($input_parameters['starttime']) && ! empty($input_parameters['starttime'])) {
            $input_parameters['starttime'] = substr($input_parameters['starttime'], 0, 10).'T00:00:00';
        } else {
            $input_parameters['starttime'] = now()->format('Y-m-d').'T00:00:00';
        }
        if (isset($input_parameters['endtime']) && ! empty($input_parameters['endtime'])) {
            $input_parameters['endtime'] = substr($input_parameters['endtime'], 0, 10).'T23:59:59';
        } else {
            $input_parameters['endtime'] = now()->format('Y-m-d').'T23:59:59';
        }
        */

        // Closure for executing a request url
        $func_execute_request_url = function () use ($input_parameters, $timeoutSeconds) {
            $stationXMLText = FindAndRetrieveStationXMLTrait::get($input_parameters, $timeoutSeconds);

            if (is_null($stationXMLText)) {
                $text = '--';
            } else {
                $stationXMLText = explode("\n", $stationXMLText);
                unset($stationXMLText[0]); // remove comment line

                $stationXMLTextExploded = explode('|', $stationXMLText[1]);
                $text = '';
                $str_pad_string = ' ';

                $net = (string) $stationXMLTextExploded[0];
                $sta = (string) $stationXMLTextExploded[1];
                $cha = (string) $stationXMLTextExploded[3];
                $loc = (string) $stationXMLTextExploded[2];
                $lat = (float) $stationXMLTextExploded[4];
                $lon = (float) $stationXMLTextExploded[5];
                $elev = (float) $stationXMLTextExploded[6];

                /* Convert 'lat' and 'lon' */
                $arrayDmsLatLon = self::DECtoDMS($lat, $lon);

                /* Format data */
                $staFormatted = self::fromFortranFormatToString('A5', $sta, $str_pad_string, STR_PAD_RIGHT);
                $netFormatted = self::fromFortranFormatToString('A2', $net, $str_pad_string);
                $chaCompFormatted = self::fromFortranFormatToString('A1', substr($cha, 2, 1), $str_pad_string);
                $chaFormatted = self::fromFortranFormatToString('A3', $cha, $str_pad_string);
                $blank = self::fromFortranFormatToString('1X', null, $str_pad_string);
                $latDegFormatted = self::fromFortranFormatToString('I2', $arrayDmsLatLon['lat']['degrees'], $str_pad_string);
                $latMinFormatted = self::fromFortranFormatToString('F7.4', $arrayDmsLatLon['lat']['minutes'], $str_pad_string);
                $latDirFormatted = self::fromFortranFormatToString('A1', $arrayDmsLatLon['lat']['direction'], $str_pad_string);
                $lonDegFormatted = self::fromFortranFormatToString('I3', $arrayDmsLatLon['lon']['degrees'], $str_pad_string);
                $lonMinFormatted = self::fromFortranFormatToString('F7.4', $arrayDmsLatLon['lon']['minutes'], $str_pad_string);
                $lonDirFormatted = self::fromFortranFormatToString('A1', $arrayDmsLatLon['lon']['direction'], $str_pad_string);
                $elevFormatted = self::fromFortranFormatToString('I4', explode('.', $elev)[0], $str_pad_string);

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
                    '1' .
                    $blank .
                    $blank .
                    '0.00' .
                    $blank .
                    $blank .
                    '0.00' .
                    $blank .
                    $blank .
                    '0.00' .
                    $blank .
                    $blank .
                    '0.00' .
                    $blank .
                    '0' .
                    $blank .
                    $blank .
                    '1.00--' .
                    "\n";
            }

            return $text;
        };

        /* START - Set Redis chache key */
        $redisCacheKey = 'station-hinv__' . $input_parameters['net'] . '.' . $input_parameters['sta'];
        if (isset($input_parameters['loc']) && !empty($input_parameters['loc'])) {
            $redisCacheKey .= '.' . $input_parameters['loc'];
        } else {
            $redisCacheKey .= '.--';
        }
        $redisCacheKey .= '.' . $input_parameters['cha'];
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
            Log::debug(' Query cache enabled (timeout=' . $timeoutSeconds . 'sec), redisCacheKey="' . $redisCacheKey . '"');
            if ($cache == 'false') {
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
            $textMessage = '!ATTENTION! - Station data not found: "' . $redisCacheKey . '"';
            if (config('apollo.cacheEnabled')) {
                if (Cache::has($redisCacheKey)) {
                    //$textMessage .= ' change cache timeout to 86400sec (24h) instead of ' . config('apollo.cacheTimeout') . 'sec .';
                    $textMessage .= ' cache key will be deleted.';
                    //Cache::put($redisCacheKey, $hyp2000StationLine, 86400);
                    Cache::forget($redisCacheKey);
                }
            }
            Log::debug('  ' . $textMessage);
            $nsc = $input_parameters['net'] . '.' . $input_parameters['sta'] . '.' . $input_parameters['cha'];
            if (config('apollo.stations_not_founded')) {
                config(['apollo.stations_not_founded' => config('apollo.stations_not_founded') . ',' . $nsc]);
            } else {
                config(['apollo.stations_not_founded' => $nsc]);
            }
        }

        Log::debug(' Output: hyp2000StationLine="' . $hyp2000StationLine . '"');
        Log::debug('END - ' . __CLASS__ . ' -> ' . __FUNCTION__);
        if ($hyp2000StationLine == '--') {
            return null;
        } else {
            return $hyp2000StationLine;
        }
    }
}
