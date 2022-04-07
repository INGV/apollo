<?php

namespace App\Api\v1\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ingv\Hyp2000Converter\Json2Arc;
use App\Http\Controllers\Controller;
use Symfony\Component\Process\Process;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
// use App\Api\Traits\ArcFileTrait;
use Ingv\StationHinv\Controllers\Hyp2000StationsController;
use Symfony\Component\Process\Exception\ProcessFailedException;


class Hyp2000Controller extends Controller
{
    // use ArcFileTrait, EarthwormTrait;

    protected $default_output = [
        "prt"
    ];

    protected $default_model = [
        "Italy",
        "5.00  0.00",
        "6.00 10.00",
        "8.10 30.00"
    ];

    protected $default_hyp2000_conf = [
        "200 T 2000 0",
        "LET 5 2 3 2 2",
        "H71 1 1 3",
        "STA './all_stations.hinv'",
        "CRH 1 './italy.crh'",
        "MAG 1 T 3 1",
        "DUR -.81 2.22 0 .0011 0, 5*0, 9999 1",
        "FC1 'D' 2 'HHZ' 'EHZ'",
        "PRE 7, 3 0 4 9, 5 6 4 9, 1 1 0 9, 2 1 0 9, 4 4 4 9, 3 0 0 9, 4 0 0 9",
        "RMS 4 .40 2 4",
        "ERR .10",
        "POS 1.78",
        "REP T T",
        "JUN T",
        "MIN 4",
        "NET 4",
        "ZTR 5 T",
        "DIS 6 100 1. 7.",
        "DAM 7 30 0.5 0.9 0.005 0.02 0.6 100 500",
        "WET 1. .75 .5 .25",
        "ERF T",
        "TOP F",
        "LST 1 1 0",
        "KPR 2",
        "COP 5",
        "CAR 3",
        "PRT '../output/hypo.prt'",
        "SUM '../output/hypo.sum'",
        "ARC '../output/hypo.arc'",
        "APP F T F",
        "CON 25 0.04 0.001",
        "PHS './input.arc'",
        "LOC"
    ];

    public function validateInputToContainsField($input_parameters, $field, $containsArray = false)
    {
        Log::debug("START - " . __CLASS__ . ' -> ' . __FUNCTION__);

        /* 1/2 - Validator */
        $validator_default_message  = [
            'required' => 'The ' . $field . ' field must exists!',
            'array' => 'The ' . $field . ' must be an array on :attribute(s)!'
        ];
        Validator::make($input_parameters, [
            $field => ['required', 'array']
        ], $validator_default_message)->validate();

        /* 2/2 - Validator */
        if ((bool)$containsArray) {
            Validator::make($input_parameters, [
                $field => [
                    function ($attribute, $value, $fail) {
                        if (isset($value) && !empty($value)) {
                            if (!array_key_exists(0, $value)) {
                                $fail('The ' . $attribute . ' must be an array of ' . $attribute . '(s).');
                            }
                        }
                    }
                ]
            ])->validate();
        }

        Log::debug("END - " . __CLASS__ . ' -> ' . __FUNCTION__);
    }

    /*
     * @param string json input picks
     * @return string json location
     */
    public function location(Request $request)
    {
        Log::debug("START - " . __CLASS__ . ' -> ' . __FUNCTION__);

        /* Validate '$request' contains 'data' */
        $input_parameters = $request->all();
        $this->validateInputToContainsField($input_parameters, 'data', false);

        /* Validate '$input_parameters['data']' contains 'TYPE_HYP2000ARC' */
        $this->validateInputToContainsField($input_parameters['data'], 'TYPE_HYP2000ARC', false);
        $arcMessage = $input_parameters['data']['TYPE_HYP2000ARC'];

        /* Validate 'arcMessage' */
        $validator_default_message  = config('apollo.validator_default_messages');
        Validator::make($arcMessage, [
            'quakeId'       => 'integer|required',
            'version'       => 'required',
        ], $validator_default_message)->validate();

        // Convert Earthworm version (that is string) to ARC version (that is 'A1' format)
        if ((isset($arcMessage['version'])) && !empty($arcMessage['version'])) {
            $type_hypocenter__name = $arcMessage['version'];
            switch ($type_hypocenter__name) {
                case 'ew prelim':
                    $version_from_ew_to_arc = 0;
                    break;
                case 'ew rapid':
                    $version_from_ew_to_arc = 1;
                    break;
                case 'ew final':
                    $version_from_ew_to_arc = 2;
                    break;
                default:
                    $version_from_ew_to_arc = 4;
            }
        } else {
            $version_from_ew_to_arc = 4;
        }
        $arcMessage['version']             = $version_from_ew_to_arc;

        /* START - Validate phases */
        if ((isset($arcMessage['phases'])) && !empty($arcMessage['phases'])) {
            foreach ($arcMessage['phases'] as $arcMessagePhase) {
                /* Validate 'phase' */
                $this->validateHyp2000ArcEwMessagePhase($arcMessagePhase);
            }
        }

        /* Ger Station-Hinv */
        $textHyp2000Stations = $this->getStationInv($input_parameters['data']['TYPE_HYP2000ARC']['phases']);

        /* Get Array ARC */
        $json2Arc = new Json2Arc();
        $textArch = $json2Arc->json2arc($arcMessage);

        /****** START - hyp2000_conf ******/
        if ((isset($input_parameters['data']['HYP2000_CONF'])) && !empty($input_parameters['data']['HYP2000_CONF'])) {
            $hyp2000Conf = $input_parameters['data']['HYP2000_CONF'];
        } else {
            $hyp2000Conf = $this->default_hyp2000_conf;
        }
        /****** END - hyp2000_conf ******/

        /****** START - model ******/
        if ((isset($input_parameters['data']['MODEL'])) && !empty($input_parameters['data']['MODEL'])) {
            $model = $input_parameters['data']['MODEL'];
        } else {
            $model = $this->default_model;
        }
        /****** END - model ******/

        /****** START - output ******/
        if ((isset($input_parameters['data']['OUTPUT'])) && !empty($input_parameters['data']['OUTPUT'])) {
            $output_format = $input_parameters['data']['OUTPUT'][0];
            switch ($output_format) {
                case 'prt':
                case 'sum':
                case 'arc':
                case 'json':
                    break;
                default:
                    $output_format = $this->default_output[0];
            }
        } else {
            $output_format = $this->default_output[0];
        }
        /****** END - output ******/

        $now            = \DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        $nowFormatted   = $now->format("Ymd_His");
        $dir_working    = "/hyp2000/" . $nowFormatted . "__" . gethostbyaddr(\request()->ip()) . "__" . \Illuminate\Support\Str::random(5);
        $dir_input      = "input";
        $dir_data       = config('filesystems.disks.data.root');

        /* Write ARC file on disk */
        $file_input_arc             = "input.arc";
        $file_input_fullpath_arc    = $dir_working . "/" . $dir_input . "/" . $file_input_arc;
        Storage::disk('data')->put($file_input_fullpath_arc, $textArch);

        /* Write hyp2000 conf file on disk */
        $textHyp2000Conf = '';
        foreach ($hyp2000Conf as $line) {
            $skip = 0;
            if (strpos($line, 'STA') !== false) {
                $skip = 1;
            }
            if (strpos($line, 'CRH') !== false) {
                $skip = 1;
            }
            if (strpos($line, 'PRT') !== false) {
                $skip = 1;
            }
            if (strpos($line, 'SUM') !== false) {
                $skip = 1;
            }
            if (strpos($line, 'ARC') !== false) {
                $skip = 1;
            }
            if (strpos($line, 'PHS') !== false) {
                $skip = 1;
            }
            if (strpos($line, 'LOC') !== false) {
                $skip = 1;
            }

            if ($skip == 0) {
                $textHyp2000Conf .= $line . " \n";
            }
        }

        $file_input_stations        = "all_stations.hinv";
        $file_input_model           = "italy.crh";
        $file_output_prt            = "hypo.prt";
        $file_output_sum            = "hypo.sum";
        $file_output_arc            = "hypo.arc";
        $file_output_json           = "hypo.json"; // generated from ew2openap
        $dir_output                 = "output";
        $file_input_conf            = "italy2000.hyp";
        $file_input_fullpath_conf   = $dir_working . "/" . $dir_input . "/" . $file_input_conf;

        $textHyp2000Conf .= "STA './" . $file_input_stations . "' \n";
        $textHyp2000Conf .= "CRH 1 './" . $file_input_model . "' \n";
        $textHyp2000Conf .= "PRT '../" . $dir_output . "/" . $file_output_prt . "' \n";
        $textHyp2000Conf .= "SUM '../" . $dir_output . "/" . $file_output_sum . "' \n";
        $textHyp2000Conf .= "ARC '../" . $dir_output . "/" . $file_output_arc . "' \n";
        $textHyp2000Conf .= "PHS './" . $file_input_arc . "' \n";
        $textHyp2000Conf .= "LOC \n";
        Storage::disk('data')->put($file_input_fullpath_conf, $textHyp2000Conf);


        /* Write hyp2000 model file on disk */
        $textModel = '';
        foreach ($model as $line) {
            $textModel .= $line . "\n";
        }
        $file_input_model                = "italy.crh";
        $file_input_fullpath_model        = $dir_working . "/" . $dir_input . "/" . $file_input_model;
        Storage::disk('data')->put($file_input_fullpath_model, $textModel);

        /* Write 'all_stations.hinv' file on disk */
        $file_input_fullpath_stations    = $dir_working . "/" . $dir_input . "/" . $file_input_stations;
        Storage::disk('data')->put($file_input_fullpath_stations, $textHyp2000Stations);


        /* Copy stations file and create output dir */
        Storage::disk('data')->makeDirectory($dir_working . "/" . $dir_output . "");

        /* !!!!!!!! START - ToDo better */
        $command =
            array_merge(
                [
                    'hostname'
                ]
            );

        /* Run process */
        Log::debug(" Running command: ", $command);
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
        /* !!!!!!!! END - ToDo better */

        /* Set command for run process */
        $command =
            array_merge(
                [
                    'docker',
                    'run',
                    '--rm',
                    '-v', $dir_data . $dir_working . ":/opt/data",
                    config('apollo.docker_hyp2000'),
                    $file_input_conf
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

        $file_output_log                = "output.log";
        $file_output_err                = "output.err";
        $file_output_fullpath_log       = $dir_working . "/" . $dir_output . "/" . $file_output_log;
        $file_output_fullpath_err       = $dir_working . "/" . $dir_output . "/" . $file_output_err;

        /* Write warnings and errors into log file */
        Log::debug(" Write warnings and errors into \"$file_output_fullpath_err\"");
        Storage::disk('data')->put($file_output_fullpath_err, $command_process->getErrorOutput());

        /* Write standard output messages into log file */
        Log::debug(" Write standard output messages into \"$file_output_fullpath_log\"");
        Storage::disk('data')->put($file_output_fullpath_log, $command_process->getOutput());

        /* Get output to return */
        Log::debug(" Get output to return");
        $contents = Storage::disk('data')->get($dir_working . "/" . $dir_output . "/" . ${"file_output_" . $output_format});

        Log::debug("END - " . __CLASS__ . ' -> ' . __FUNCTION__);
        if ($output_format == 'json') {
            //$contents = date_format(date_create($arcMessage['originTime']), 'Y-m-d');
            //$contents .= "\n\n";
            return response()->json(json_decode($contents, true), 200, [], JSON_PRETTY_PRINT);
        } else {
            /* set headers */
            $headers['Content-type'] = 'text/plain';
            return response()->make($contents, 200, $headers);
        }
    }

    public function getStationInv($phases)
    {
        Log::debug("START - " . __CLASS__ . ' -> ' . __FUNCTION__);

        /* Build 'all_stations.hinv' file */
        $textHyp2000Stations = '';
        $Hyp2000StationsController = new Hyp2000StationsController;

        /* Number of stations */
        $n_hyp2000Sation = count($phases);
        $count = 1;
        foreach ($phases as $phase) {
            // cerca su pat altrimenti sat altriment now
            if (isset($phase['Pat']) && !empty($phase['Pat'])) {
                $starttime = substr($phase['Pat'], 0, 10) . 'T00:00:00';
                $endtime = substr($phase['Pat'], 0, 10) . 'T23:59:59';
            } else if (isset($phase['Sat']) && !empty($phase['Sat'])) {
                $starttime = substr($phase['Sat'], 0, 10) . 'T00:00:00';
                $endtime = substr($phase['Sat'], 0, 10) . 'T23:59:59';
            } else {
                $starttime = now()->format('Y-m-d') . 'T00:00:00';
                $endtime = now()->format('Y-m-d') . 'T23:59:59';
            }

            Log::info($count . "/" . $n_hyp2000Sation . " - Searching: " . $phase['net'] . "." . $phase['sta'] . "." . $phase['loc'] . "." . $phase['comp']);
            $stationLine = $Hyp2000StationsController->query(new Request([
                'net'           => $phase['net'],
                'sta'           => $phase['sta'],
                'cha'           => $phase['comp'],
                'loc'           => $phase['loc'],
                'starttime'     => $starttime,
                'endtime'       => $endtime,
                'cache'         => 'true'
            ]));
            $textHyp2000Stations .= $stationLine->content();
            $count++;
        }
        return $textHyp2000Stations;
    }

    /**
     * @brief Validate a single phase fields from 'ewMessage'
     * 
     * Validate a single phase fields from 'ewMessage' generated from 'ew2openapi' output
     * 
     * @param type $phase
     */
    public function validateHyp2000ArcEwMessagePhase($phase)
    {
        Log::debug("START - " . __CLASS__ . ' -> ' . __FUNCTION__);

        // START - Validator
        $validator_default_message  = config('apollo.validator_default_messages');
        $validator = Validator::make($phase, [
            'sta'                   => 'required|string',
            'net'                   => 'string|between:1,2',
            'comp'                  => 'string|size:3',
            'loc'                   => 'nullable|string',
            'Plabel'                => 'string|nullable',
            'Slabel'                => 'string|nullable',
            'Ponset'                => 'string|nullable',
            'Sonset'                => 'string|nullable',
            'Pres'                  => 'numeric|nullable',
            'Sres'                  => 'numeric|nullable',
            'Pqual'                 => 'integer|nullable|in:0,1,2,3,4,9', // '9' is not a valid value but sometimes it is used from EW when 'Pqual' is 'null'; this will be filtered in the 'IngvNTEwController.hyp2000arc()' method.
            'Squal'                 => 'integer|nullable|in:0,1,2,3,4,9', // '9' is not a valid value but sometimes it is used from EW when 'Pqual' is 'null'; this will be filtered in the 'IngvNTEwController.hyp2000arc()' method.
            'codalen'               => 'integer|nullable',
            'codawt'                => 'integer|nullable',
            'Pfm'                   => 'string|size:1|nullable',
            'Sfm'                   => 'string|size:1|nullable',
            'datasrc'               => 'string|nullable',
            'Md'                    => 'numeric|nullable',
            'azm'                   => 'numeric|nullable',
            'takeoff'               => 'integer|nullable',
            'dist'                  => 'numeric|nullable',
            'Pwt'                   => 'numeric|nullable',
            'Swt'                   => 'numeric|nullable',
            'pamp'                  => 'integer|nullable',
            'codalenObs'            => 'integer|nullable',
            'amplitude'             => 'numeric|nullable',
            'ampUnitsCode'          => 'integer|nullable',
            'ampType'               => 'integer|nullable',
            'ampMag'                => 'numeric|nullable',
            'ampMagWeightCode'      => 'integer|nullable',
            'importanceP'           => 'numeric|nullable',
            'importanceS'           => 'numeric|nullable',
        ], $validator_default_message);

        /* Check 'P' fileds only when 'Ponset' is set */
        $validator->sometimes('Pat', 'required|date', function ($input) {
            return isset($input->Ponset);
        });

        /* Check 'S' fileds only when 'Sonset' is set */
        $validator->sometimes('Sat', 'required|date', function ($input) {
            return isset($input->Sonset);
        });

        $validator->validate();

        // END - Validator
        Log::debug("END - " . __CLASS__ . ' -> ' . __FUNCTION__);
    }
}
