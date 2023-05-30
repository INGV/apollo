<?php

namespace App\Api\v2\Controllers;

use App\Api\v2\Models\PyMLModel;
use Illuminate\Support\Facades\Log;
use App\Api\v2\Requests\PyMLRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use App\Api\v2\Traits\FindAndRetrieveStationXMLTrait;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PyMLController extends Controller
{
    use FindAndRetrieveStationXMLTrait;

    protected $default_pyml_conf = [
        'iofilenames' => [
            'magnitudes' => '/app/storage/app/data/pyml/---dir_random_name---/pyml_magnitudes.csv', // deve essere lo stesso 'path' inserito nello yml 'docker-compose', nella direttiva 'command:'
            'log' => '/app/storage/app/data/pyml/---dir_random_name---/pyml_general.log', // deve essere lo stesso 'path' inserito nello yml 'docker-compose', nella direttiva 'command:'
        ],
        'preconditions' => [
            'theoretical_p' => false,
            'theoretical_s' => false,
            'delta_corner' => 5,
            'max_lowcorner' => 15,
        ],
        'station_magnitude' => [
            'station_magnitude' => 'meanamp',
            'amp_mean_type' => 'geo',
            'delta_peaks' => 1,
            'use_stcorr_hb' => true,
            'use_stcorr_db' => true,
            'when_no_stcorr_hb' => true,
            'when_no_stcorr_db' => true,
        ],
        'event_magnitude' => [
            'mindist' => 10,
            'maxdist' => 600,
            'hm_cutoff' => [
                12,
                13,
            ],
            'outliers_max_it' => 10,
            'outliers_red_stop' => 0.1,
            'outliers_nstd' => 1,
            'outliers_cutoff' => 0.1,
        ],
    ];

    /**
     * Checks if multiple keys exist in an array
     * https://www.geeksforgeeks.org/how-to-search-by-multiple-key-value-in-php-array/
     *
     * @param  array  $array
     * @param  array  $keys
     * @return array
     */
    public static function array_keys_exist($array, $search_list)
    {
        // Create the result array
        $result = [];

        // Iterate over each array element
        foreach ($array as $key => $value) {
            // Iterate over each search condition
            foreach ($search_list as $k => $v) {
                // If the array element does not meet
                // the search condition then continue
                // to the next element
                if (!isset($value[$k]) || $value[$k] != $v) {
                    // Skip two loops
                    continue 2;
                }
            }
            // Append array element's key to the
            //result array
            $result[] = $value;
        }
        // Return result
        return $result;
    }

    /*
     * @param string json input picks
     * @return string json location
     */
    public function location(PyMLRequest $request)
    {
        Log::info('START - ' . __CLASS__ . ' -> ' . __FUNCTION__);
        $pymlTimeStart = microtime(true);

        /* Get validated input */
        $input_parameters = $request->validated();

        /****** START - output ******/
        $output_format = $input_parameters['data']['output'];
        /****** END - output ******/

        /****** START - amplitudes ******/
        $n = 1;
        $tmpAmplitudeChaComponents = [];
        $nAmplitudes = count($input_parameters['data']['amplitudes']);
        foreach ($input_parameters['data']['amplitudes'] as &$amplitude) {
            $pyMLCoordArray = PyMLModel::getCoord($amplitude, config('apollo.cacheTimeout'), $n . '/' . $nAmplitudes . ' - ');

            if (empty($pyMLCoordArray)) {
                Log::debug(' No, coordinates');
            } else {
                /* Add coord to amplitude */
                $amplitude['lat'] = $pyMLCoordArray['lat'];
                $amplitude['lon'] = $pyMLCoordArray['lon'];
                $amplitude['elev'] = $pyMLCoordArray['elev'];

                /* build Cha->Component array */
                $net = $amplitude['net'];
                $sta = $amplitude['sta'];
                $cha = $amplitude['cha'];
                $loc = $amplitude['loc'] ?? '--';
                $tmpAmplitudeChaComponents[$net . '.' . $sta . '.' . $loc . '.' . substr($cha, 0, 2)][] = substr($cha, 2, 1); // $tmpAmplitudeChaComponents['IV.ACER.--.HH'] => ['N', 'E']
            }
            $n++;
        }
        /****** END - amplitudes ******/

        /* Set variables */
        $now = \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        $nowFormatted = $now->format('Ymd_His');
        $dir_random_name = $nowFormatted . '__' . gethostbyaddr(\request()->ip()) . '__' . \Illuminate\Support\Str::random(5);
        $dir_working = '/pyml/' . $dir_random_name;

        /****** START - pyml_conf ******/
        /* Set pyml_conf */
        foreach ($this->default_pyml_conf as $key => $value) {
            if (empty($input_parameters['data']['pyml_conf'][$key])) {
                $input_parameters['data']['pyml_conf'][$key] = $this->default_pyml_conf[$key];
            } else {
                $input_parameters['data']['pyml_conf'][$key] = array_merge($this->default_pyml_conf[$key], $input_parameters['data']['pyml_conf'][$key]);
            }
        }

        /* Update 'iofilenames' key */
        $input_parameters['data']['pyml_conf']['iofilenames']['magnitudes'] = str_replace('---dir_random_name---', $dir_random_name, $input_parameters['data']['pyml_conf']['iofilenames']['magnitudes']);
        $input_parameters['data']['pyml_conf']['iofilenames']['log'] = str_replace('---dir_random_name---', $dir_random_name, $input_parameters['data']['pyml_conf']['iofilenames']['log']);
        /****** END - pyml_conf ******/

        /* Write input.json */
        $file_input_json = 'input.json';
        $file_input_fullpath_arc = $dir_working . '/' . $file_input_json;
        Storage::disk('data')->put($file_input_fullpath_arc, json_encode($input_parameters));

        /* !!!!!!!! START - Call pyml */
        Log::debug(' Call pyml container:');
        /* Set variables */
        $url = "http://pyml:8080/get?dir=$dir_random_name";

        try {
            Log::debug('   step_1a: ' . $url);
            /* https://laravel.com/docs/8.x/http-client */
            $response = Http::timeout(5)->get($url);
            $responseStatus = $response->status() ?? 500;

            Log::debug('   step_2');
            $response->throw();

            Log::debug('   step_3');
            if ($responseStatus == 200) {
                Log::debug('   step_4a - httpStatusCode=' . $responseStatus);
                $outputData = $response->body();
            } else {
                Log::debug('   step_4b - httpStatusCode=' . $responseStatus);
            }
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::debug('   step_1b');
            Log::debug('    getCode:' . $e->getCode());
            Log::debug('    getMessage:' . $e->getMessage());
            abort($responseStatus, $e->getMessage());
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::debug('   step_1c');
            Log::debug('    getCode:' . $e->getCode());
            Log::debug('    getMessage:' . $e->getMessage());
            abort(500, $e->getMessage());
        } catch (\Exception $e) {
            Log::debug('   step_1d');
            Log::debug('    getCode:' . $e->getCode());
            Log::debug('    getMessage:' . $e->getMessage());
            abort($e->getCode() ?? 500, $e->getMessage());
        }
        Log::debug(' Done');
        /* !!!!!!!! END - Call pyml */

        if ($output_format == 'text') {
            $contents = Storage::disk('data')->get($dir_working . '/pyml_magnitudes.csv');
            /* set headers */
            $headers['Content-type'] = 'text/plain';

            $pymlExecutionTime = number_format((microtime(true) - $pymlTimeStart) * 1000, 2);
            Log::info('END - ' . __CLASS__ . ' -> ' . __FUNCTION__ . ' | pymlExecutionTime=' . $pymlExecutionTime . ' Milliseconds');
            return response()->make($contents, 200, $headers);
        } else if ($output_format == 'csv2json') {
            /* Get pyml csv file */
            $csvToArray = [];
            if (($open = fopen(Storage::disk('data')->path($dir_working . '/pyml_magnitudes.csv'), 'r')) !== false) {
                while (($data = fgetcsv(
                    $open,
                    1000,
                    ';'
                )) !== false) {
                    $csvToArray[] = $data;
                }
                fclose($open);
            }

            /* Build output */
            $output['data']['random_string'] = $dir_random_name;

            /* START - Magnitudes */
            $output['data']['magnitudes'] = [
                'hb' => [
                    'ml' => $csvToArray[1][1],
                    'std' => $csvToArray[1][2],
                    'totsta' => $csvToArray[1][3],
                    'usedsta' => (string) intval($csvToArray[1][4]),
                ],
                'db' => [
                    'ml' => $csvToArray[1][5],
                    'std' => $csvToArray[1][6],
                    'totsta' => $csvToArray[1][7],
                    'usedsta' => (string) intval($csvToArray[1][8]),
                ],
                'ampmethod' => $csvToArray[1][9],
                'magmethod' => $csvToArray[1][10],
                'loopexitcondition' => $csvToArray[1][11],
            ];
            /* END - Magnitudes */

            /* START - Stationmagnitude */
            unset($csvToArray[0]);  // Remove header
            unset($csvToArray[1]);  // Remove origin magnitude
            foreach ($csvToArray as $csvToArrayLine) {
                Log::debug(' ==== csvToArrayLine ====:', $csvToArrayLine);
                [$mlcha, $scnl, $ml_hb, $ml_hb_weight, $e, $ml_db, $ml_db_weight] = explode(' ', $csvToArrayLine[0]) + ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']; // the second array (['a', 'b', ecc..]) is used to set 'default' value.

                if (strtoupper($mlcha) == 'MLCHA') {
                    /* Get SCNL line */
                    $search_items = [];
                    $scnl_exploded = explode('_', $scnl);
                    $net = $scnl_exploded[0];
                    $sta = $scnl_exploded[1];
                    if (strtolower($scnl_exploded[2]) == 'none') {
                        $loc = null;
                    } else {
                        $loc = $scnl_exploded[2];
                        $search_items['loc'] = $loc;
                    }
                    $cha = $scnl_exploded[3];
                    $search_items['net'] = $net;
                    $search_items['sta'] = $sta;
                    $search_items['cha'] = $cha;

                    /** Search items, into:
                     *   '$input_parameters['data']['amplitudes']'
                     *  with:
                     *   'net=$net', 'sta=$sta', 'cha=$cha', (optional 'loc=$loc').
                     */
                    $inputAmplitude = self::array_keys_exist($input_parameters['data']['amplitudes'], $search_items);

                    /* build final array */
                    $stationmagnitude = $inputAmplitude[0];
                    $stationmagnitude['hb'] = [
                        'ml' => $ml_hb,
                        'w' => $ml_hb_weight,
                    ];
                    $stationmagnitude['db'] = [
                        'ml' => $ml_db,
                        'w' => $ml_db_weight,
                    ];
                    $output['data']['stationmagnitudes'][] = $stationmagnitude;
                } else {
                    Log::debug('  the line doesn\'t start with "MLCHA"; skip...');
                }
            }
            /* END - Stationmagnitude */

            $pymlExecutionTime = number_format((microtime(true) - $pymlTimeStart) * 1000, 2);
            Log::info('END - ' . __CLASS__ . ' -> ' . __FUNCTION__ . ' | pymlExecutionTime=' . $pymlExecutionTime . ' Milliseconds');
            return response()->json($output, 200, [], JSON_PRETTY_PRINT);
        } else {
            Log::debug(' Get: ' . $dir_working . '/output.log');
            $pymlOutput = Storage::disk('data')->get($dir_working . '/output.log');
            $output['data'] = json_decode($pymlOutput, true);
            $output['data']['random_string'] = $dir_random_name;

            Log::debug(' STA_NOT_FOUNDED:' . config('apollo.stations_not_founded'));
            $pymlExecutionTime = number_format((microtime(true) - $pymlTimeStart) * 1000, 2);
            Log::info('END - ' . __CLASS__ . ' -> ' . __FUNCTION__ . ' | pymlExecutionTime=' . $pymlExecutionTime . ' Milliseconds');
            return response()->json($output, 200, [], JSON_PRETTY_PRINT);
        }
    }
}
