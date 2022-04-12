<?php

namespace App\Api\v2\Controllers;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Symfony\Component\Process\Process;
use App\Api\v2\Requests\PyMLRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PyMLController extends Controller
{
    protected $default_output = "prt";

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

    /*
     * @param string json input picks
     * @return string json location
     */
    public function location(PyMLRequest $request)
    {
        Log::debug("START - " . __CLASS__ . ' -> ' . __FUNCTION__);
        $locationTimeStart = microtime(true);

        $input_parameters = $request->validated();

        /* Get amplitudes */
        $amplitudes = $input_parameters['data']['amplitudes'];

        dd($input_parameters, $amplitudes);



        /****** START - hyp2000_conf ******/
        if ((isset($input_parameters['data']['hyp2000_conf'])) && !empty($input_parameters['data']['hyp2000_conf'])) {
            $hyp2000Conf = $input_parameters['data']['hyp2000_conf'];
        } else {
            $hyp2000Conf = $this->default_hyp2000_conf;
        }
        /****** END - hyp2000_conf ******/

        /****** START - model ******/
        if ((isset($input_parameters['data']['model'])) && !empty($input_parameters['data']['model'])) {
            $model = $input_parameters['data']['model'];
        } else {
            $model = $this->default_model;
        }
        /****** END - model ******/

        /****** START - output ******/
        if ((isset($input_parameters['data']['output'])) && !empty($input_parameters['data']['output'])) {
            $output_format = $input_parameters['data']['output'];
        } else {
            $output_format = $this->default_output;
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

        /* !!!!!!!! START - Get 'whoami' ToDo better */
        $command =
            array_merge(
                [
                    'whoami'
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
        /* !!!!!!!! END - Get 'whoami' ToDo better */

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
        /* !!!!!!!! END - Get 'docker run' ToDo better */

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

        $locationExecutionTime = number_format((microtime(true) - $locationTimeStart) * 1000, 2);
        Log::info("END - " . __CLASS__ . ' -> ' . __FUNCTION__ . ' | locationExecutionTime=' . $locationExecutionTime . ' Milliseconds');
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
}
