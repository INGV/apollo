<?php

namespace App\Api\v2\Controllers;

use App\Api\v2\Models\PyMLModel;
use Illuminate\Support\Facades\Log;
use App\Api\v2\Requests\PyMLRequest;
use App\Http\Controllers\Controller;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Api\v2\Traits\FindAndRetrieveStationXMLTrait;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PyMLController extends Controller
{
    use FindAndRetrieveStationXMLTrait;

    protected $default_pyml_conf = [
        'iofilenames' => [
            'magnitudes' => '/opt/data/pyml_magnitudes.csv',
            'log' => '/opt/data/pyml_general.log'
        ],
        'preconditions' => [
            'theoretical_p' => false,
            'theoretical_s' => false,
            'delta_corner' => 5,
            'max_lowcorner' => 15
        ],
        'station_magnitude' => [
            'delta_peaks' => 1,
            'use_stcorr_hb' => true,
            'use_stcorr_db' => true,
            'when_no_stcorr_hb' => true,
            'when_no_stcorr_db' => true
        ],
        'event_magnitude' => [
            'mindist' => 10,
            'maxdist' => 600,
            'hm_cutoff' => [
                12,
                13
            ],
            'outliers_max_it' => 10,
            'outliers_red_stop' => 0.1,
            'outliers_nstd' => 1,
            'outliers_cutoff' => 0.1
        ]
    ];

    /*
     * @param string json input picks
     * @return string json location
     */
    public function location(PyMLRequest $request)
    {
        Log::debug("START - " . __CLASS__ . ' -> ' . __FUNCTION__);
        $locationTimeStart = microtime(true);

        /* Get validated input */
        $input_parameters = $request->validated();

        /****** START - output ******/
        $output_format = $input_parameters['data']['output'];
        /****** END - output ******/

        /****** START - amplitudes ******/
        foreach ($input_parameters['data']['amplitudes'] as &$amplitude) {
            $pyMLCoordArray = PyMLModel::getCoord($amplitude, config('apollo.cacheTimeout'));

            if (empty($pyMLCoordArray)) {
                Log::debug(" No, coordinates");
            } else {
                $amplitude['lat'] = $pyMLCoordArray['lat'];
                $amplitude['lon'] = $pyMLCoordArray['lon'];
                $amplitude['elev'] = $pyMLCoordArray['elev'];
            }
        }
        /****** END - amplitudes ******/

        /****** START - pyml_conf ******/
        /*
        foreach ($input_parameters['data']['pyml_conf'] as $key => $value) {
            $input_parameters['data']['pyml_conf'][$key] = array_merge($input_parameters['data']['pyml_conf'][$key], $this->default_pyml_conf[$key]);
        }
        */
        foreach ($this->default_pyml_conf as $key => $value) {
            if (empty($input_parameters['data']['pyml_conf'][$key])) {
                $input_parameters['data']['pyml_conf'][$key] = $this->default_pyml_conf[$key];
            } else {
                $input_parameters['data']['pyml_conf'][$key] = array_merge($this->default_pyml_conf[$key], $input_parameters['data']['pyml_conf'][$key]);
            }
        }
        /****** END - pyml_conf ******/

        /* Set variables */
        $now            = \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        $nowFormatted   = $now->format("Ymd_His");
        $random_name    = $nowFormatted . "__" . gethostbyaddr(\request()->ip()) . "__" . \Illuminate\Support\Str::random(5);
        $dir_working    = "/pyml/" . $random_name;
        $dir_data       = config('filesystems.disks.data.root');

        /* Write input.json */
        $file_input_json            = "input.json";
        $file_input_fullpath_arc    = $dir_working . "/" . $file_input_json;
        Storage::disk('data')->put($file_input_fullpath_arc, json_encode($input_parameters));

        /* !!!!!!!! START - Get 'id -u' ToDo better */
        $command =
            array_merge(
                [
                    'id',
                    '-u'
                ]
            );

        /* Run process */
        Log::debug(" Running command: ", $command);
        $command_timeout = 120;
        $command_process = new Process($command);
        $command_process->setTimeout($command_timeout);
        $command_process->run();
        $uid = preg_replace("/\r|\n/", "", $command_process->getOutput());
        Log::debug(" getOutput:" . $uid);
        Log::debug(" getErrorOutput:" . $command_process->getErrorOutput());
        if (!$command_process->isSuccessful()) {
            throw new ProcessFailedException($command_process);
        }
        Log::debug(" Done.");
        /* !!!!!!!! END - Get 'id -u' ToDo better */

        /* !!!!!!!! START - Get 'id -g' ToDo better */
        $command =
            array_merge(
                [
                    'id',
                    '-g'
                ]
            );

        /* Run process */
        Log::debug(" Running command: ", $command);
        $command_timeout = 120;
        $command_process = new Process($command);
        $command_process->setTimeout($command_timeout);
        $command_process->run();
        $gid = preg_replace("/\r|\n/", "", $command_process->getOutput());
        Log::debug(" getOutput:" . $gid);
        Log::debug(" getErrorOutput:" . $command_process->getErrorOutput());
        if (!$command_process->isSuccessful()) {
            throw new ProcessFailedException($command_process);
        }
        Log::debug(" Done.");
        /* !!!!!!!! END - Get 'id -g' ToDo better */

        /* !!!!!!!! START - Get 'docker run' ToDo better */
        $command =
            array_merge(
                [
                    'docker',
                    'run',
                    '--rm',
                    '--user',
                    $uid . ':' . $gid,
                    '-v', $dir_data . $dir_working . ":/opt/data",
                    config('apollo.docker_pyml'),
                    '--json', '/opt/data/input.json'
                ]
            );

        /* Run process */
        Log::debug(" Running docker: ", $command);
        $command_timeout = 120;
        $command_process = new Process($command);
        $command_process->setTimeout($command_timeout);
        $command_process->run();
        Log::debug(" getOutput:" . $command_process->getOutput());
        Log::debug(" getErrorOutput:" . $command_process->getErrorOutput());
        if (!$command_process->isSuccessful()) {
            throw new ProcessFailedException($command_process);
        }
        Log::debug(" Done.");
        /* !!!!!!!! END - Get 'docker run' ToDo better */

        /* */
        $file_output_log                = "output.log";
        $file_output_err                = "output.err";
        $file_output_fullpath_log       = $dir_working . "/" . $file_output_log;
        $file_output_fullpath_err       = $dir_working . "/" . $file_output_err;

        /* Write warnings and errors into log file */
        Log::debug(" Write warnings and errors into \"$file_output_fullpath_err\"");
        Storage::disk('data')->put($file_output_fullpath_err, $command_process->getErrorOutput());

        /* Write standard output messages into log file */
        Log::debug(" Write standard output messages into \"$file_output_fullpath_log\"");
        Storage::disk('data')->put($file_output_fullpath_log, $command_process->getOutput());

        /* Get pyml log file */
        /*
        Log::debug(" Get output to return");
        $contents = Storage::disk('data')->get($dir_working . "/pyml_general.log");
        $pyml_log = explode("\n", $contents);
        */

        if ($output_format == 'text') {
            $contents = Storage::disk('data')->get($dir_working . "/pyml_magnitudes.csv");
            /* set headers */
            $headers['Content-type'] = 'text/plain';
            return response()->make($contents, 200, $headers);
        } else {
            /* Get pyml csv file */
            $csvToArray = [];
            if (($open = fopen($dir_data . $dir_working . '/pyml_magnitudes.csv', "r")) !== FALSE) {
                while (($data = fgetcsv(
                    $open,
                    1000,
                    ";"
                )) !== FALSE) {
                    $csvToArray[] = $data;
                }
                fclose($open);
            }

            /* Build output */
            $output['data']['random_string']    = $random_name;
            //$output['data']['eventid']          = $csvToArray[1][0];

            /* START - Magnitudes */
            $output['data']['magnitudes'] = [
                'hb' => [
                    'ml'        => $csvToArray[1][1],
                    'std'       => $csvToArray[1][2],
                    'totsta'    => $csvToArray[1][3],
                    'usedsta'   => (string)intval($csvToArray[1][4]),
                ],
                'db' => [
                    'ml'        => $csvToArray[1][5],
                    'std'       => $csvToArray[1][6],
                    'totsta'    => $csvToArray[1][7],
                    'usedsta'   => (string)intval($csvToArray[1][8]),
                ],
                'ampmethod'     => $csvToArray[1][9],
                'magmethod'     => $csvToArray[1][10],
                'loopexitcondition' => $csvToArray[1][11]
            ];
            /* END - Magnitudes */

            /* START - Stationmagnitude */
            unset($csvToArray[0]);  // Remove header
            unset($csvToArray[1]);  // Remove origin magnitude
            foreach ($csvToArray as $value) {
                list($a, $b, $c, $d, $e, $f, $g) = explode(" ", $value[0]);

                /* Get SCNL */
                $b_exploded = explode('_', $b);
                $net = $b_exploded[0];
                $sta = $b_exploded[1];
                if ($b_exploded[2] == 'None') {
                    $loc = '--';
                } else {
                    $loc = $b_exploded[2];
                };
                $cha = $b_exploded[3];

                foreach (['Z', 'N', 'E'] as $component) {
                    $stationmagnitude = [
                        'net' => $net,
                        'sta' => $sta,
                        'cha' => $cha . $component,
                        'loc' => $loc,
                        'hb'  => [
                            'ml' => $c,
                            'w' => $d,
                        ],
                        'db'  => [
                            'ml' => $f,
                            'w' => $g,
                        ]
                    ];
                    $output['data']['stationmagnitudes'][] = $stationmagnitude;
                }
            }
            /* END - Stationmagnitude */

            $locationExecutionTime = number_format((microtime(true) - $locationTimeStart) * 1000, 2);
            Log::info("END - " . __CLASS__ . ' -> ' . __FUNCTION__ . ' | locationExecutionTime=' . $locationExecutionTime . ' Milliseconds');
            return response()->json($output, 200, [], JSON_PRETTY_PRINT);
        }




        /*
        $amplitude = [];
        unset($input_parameters['data']['origin']);
        unset($input_parameters['data']['amplitudes']);
        $url = 'http://caravel.int.ingv.it/api/quakedb/v1/event?eventid=28745631';
        $json = json_decode(file_get_contents($url), true);

        $input_parameters['data']['origin']['lat'] = $json['data']['event']['origins'][4]['lat'];
        $input_parameters['data']['origin']['lon'] = $json['data']['event']['origins'][4]['lon'];
        $input_parameters['data']['origin']['depth'] = $json['data']['event']['origins'][4]['depth'];

        foreach ($json['data']['event']['origins'][4]['magnitudes'][0]['stationmagnitudes'] as $stationmagnitude) {
            $net = $stationmagnitude['net'];
            $sta = $stationmagnitude['sta'];
            $cha = $stationmagnitude['cha'];
            if ($stationmagnitude['loc'] == '--') {
                $loc = null;
            } else {
                $loc = $stationmagnitude['loc'];
            }
            $amp1 = $stationmagnitude['amp1'];
            $time1 = $stationmagnitude['time1'];
            $amp2 = $stationmagnitude['amp2'];
            $time2 = $stationmagnitude['time2'];

            $amplitude = [
                'net' => $net,
                'sta' => $sta,
                'cha' => $cha,
                'loc' => $loc,
                'amp1' => $amp1,
                'time1' => $time1,
                'amp2' => $amp2,
                'time2' => $time2,
            ];
            $pyMLCoordArray = PyMLModel::getCoord($amplitude);

            if (empty($pyMLCoordArray)) {
                Log::debug(" No, coordinates");
            } else {
                $amplitude['lat'] = $pyMLCoordArray['lat'];
                $amplitude['lon'] = $pyMLCoordArray['lon'];
                $amplitude['elev'] = $pyMLCoordArray['elev'];
            }

            $input_parameters['data']['amplitudes'][] = $amplitude;
        }
        $locationExecutionTime = number_format((microtime(true) - $locationTimeStart) * 1000, 2);
        Log::info("END - " . __CLASS__ . ' -> ' . __FUNCTION__ . ' | locationExecutionTime=' . $locationExecutionTime . ' Milliseconds');
        return response()->json($input_parameters, 200, [], JSON_PRETTY_PRINT);        
        */
    }
}
