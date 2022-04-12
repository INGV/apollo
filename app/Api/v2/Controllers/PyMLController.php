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

        /****** START - amplitudes ******/
        foreach ($input_parameters['data']['amplitudes'] as &$amplitude) {
            $pyMLCoordArray = PyMLModel::getCoord($amplitude);

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
        foreach ($input_parameters['data']['pyml_conf'] as $key => $value) {
            $input_parameters['data']['pyml_conf'][$key] = array_merge($input_parameters['data']['pyml_conf'][$key], $this->default_pyml_conf[$key]);
        }
        /****** END - pyml_conf ******/

        /* Set variables */
        $now            = \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        $nowFormatted   = $now->format("Ymd_His");
        $dir_working    = "/pyml/" . $nowFormatted . "__" . gethostbyaddr(\request()->ip()) . "__" . \Illuminate\Support\Str::random(5);
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

        /* Read CSV */
        $csvToArray = [];
        if (($open = fopen($dir_data . $dir_working . '/pyml_magnitudes.csv', "r")) !== FALSE) {
            while (($data = fgetcsv($open, 1000, ";")) !== FALSE) {
                $csvToArray[] = $data;
            }
            fclose($open);
        }

        /* Build output */
        /* START - Event magnitude */
        $output = [];
        foreach ($csvToArray[0] as $key => $value) {
            $output['data']['magnitude'][$value] = $csvToArray[1][$key];
        }
        /* END - Event magnitude */

        /* START - Stationmagnitude */
        unset($csvToArray[0]);
        unset($csvToArray[1]);
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

            $stationmagnitude = [
                'net' => $net,
                'sta' => $sta,
                'cha' => $cha,
                'loc' => $loc,
                'a' => $a,
                'c' => $c,
                'd' => $d,
                'f' => $f,
                'g' => $g,
            ];
            $output['data']['stationmagnitudes'][] = $stationmagnitude;
        }
        /* END - Stationmagnitude */

        $locationExecutionTime = number_format((microtime(true) - $locationTimeStart) * 1000, 2);
        Log::info("END - " . __CLASS__ . ' -> ' . __FUNCTION__ . ' | locationExecutionTime=' . $locationExecutionTime . ' Milliseconds');
        return response()->json($output, 200, [], JSON_PRETTY_PRINT);


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
