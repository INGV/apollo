<?php

namespace App\Api\v2\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class Hyp2000Test extends TestCase
{
    public function test_hyp2000_1()
    {
        $dataInput = '{
            "data": {
                "hyp2000_conf": [
                    "200 T 2000 0",
                    "LET 5 2 3 2 2",
                    "H71 1 1 3",
                    "STA \'./all_stations.hinv\'",
                    "CRH 1 \'./italy.crh\'",
                    "MAG 1 T 3 1",
                    "DUR -.81 2.22 0 .0011 0); 5*0); 9999 1",
                    "FC1 \'D\' 2 \'HHZ\' \'EHZ\'",
                    "PRE 7); 3 0 4 9); 5 6 4 9); 1 1 0 9); 2 1 0 9); 4 4 4 9); 3 0 0 9); 4 0 0 9",
                    "RMS 4 .40 2 4",
                    "ERR .10",
                    "POS 1.78",
                    "REP T T",
                    "JUN T",
                    "MIN 4",
                    "NET 4",
                    "ZTR 10  F",
                    "DIS 6 100 1. 7.",
                    "DAM 7 30 0.5 0.9 005 02 0.6 100 500",
                    "WET 1. .75 .5 .25",
                    "ERF T",
                    "TOP F",
                    "LST 1 1 0",
                    "KPR 2",
                    "COP 5",
                    "CAR 3",
                    "PRT \'../output/hypo.prt\'",
                    "SUM \'../output/hypo.sum\'",
                    "ARC \'../output/hypo.arc\'",
                    "APP F T F",
                    "CON 25 04 001",
                    "PHS \'./input.arc\'",
                    "LOC"
                ],
                "model": [
                    "Italy",
                    "5.00 00",
                    "6.50 11.10",
                    "8.05 26.90"
                ],
                "output": "json",
                "phases": [
                    {
                        "net": "IV",
                        "sta": "NRCA",
                        "cha": "HNZ",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:24.080Z",
                        "isc_code": "P",
                        "firstmotion": "U",
                        "emersio": null,
                        "weight": 2,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "MC2",
                        "cha": "EHZ",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:24.520Z",
                        "isc_code": "P",
                        "firstmotion": "D",
                        "emersio": null,
                        "weight": 2,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "MC2",
                        "cha": "EHN",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:26.600Z",
                        "isc_code": "S",
                        "firstmotion": null,
                        "emersio": null,
                        "weight": 1,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "MTRA",
                        "cha": "EHZ",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:24.460Z",
                        "isc_code": "PG",
                        "firstmotion": "D",
                        "emersio": null,
                        "weight": 1,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "MTRA",
                        "cha": "EHN",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:26.930Z",
                        "isc_code": "S",
                        "firstmotion": null,
                        "emersio": null,
                        "weight": 1,
                        "amplitude": 0.23,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "MF5",
                        "cha": "EHZ",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:26.930Z",
                        "isc_code": "PG",
                        "firstmotion": "U",
                        "emersio": null,
                        "weight": 1,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "LNSS",
                        "cha": "HHZ",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:26.410Z",
                        "isc_code": "PG",
                        "firstmotion": "D",
                        "emersio": null,
                        "weight": 1,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "SMA1",
                        "cha": "EHZ",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:25.400Z",
                        "isc_code": "PG",
                        "firstmotion": "U",
                        "emersio": null,
                        "weight": 1,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "SMA1",
                        "cha": "EHE",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:28.680Z",
                        "isc_code": "S",
                        "firstmotion": null,
                        "emersio": null,
                        "weight": 1,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "FDMO",
                        "cha": "HHZ",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:26.770Z",
                        "isc_code": "P",
                        "firstmotion": "U",
                        "emersio": null,
                        "weight": 1,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "MMO1",
                        "cha": "EHZ",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:24.410Z",
                        "isc_code": "PN",
                        "firstmotion": null,
                        "emersio": null,
                        "weight": 1,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "GAVE",
                        "cha": "EHZ",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:26.900Z",
                        "isc_code": "PG",
                        "firstmotion": null,
                        "emersio": null,
                        "weight": 1,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "CSP1",
                        "cha": "EHZ",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:27.260Z",
                        "isc_code": "PN",
                        "firstmotion": "U",
                        "emersio": null,
                        "weight": 1,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "TERO",
                        "cha": "HHZ",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:28.400Z",
                        "isc_code": "P",
                        "firstmotion": null,
                        "emersio": null,
                        "weight": 0,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "SAP2",
                        "cha": "EHN",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:30.730Z",
                        "isc_code": "PG",
                        "firstmotion": "D",
                        "emersio": null,
                        "weight": 0,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "MML1",
                        "cha": "EHZ",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:27.620Z",
                        "isc_code": "PN",
                        "firstmotion": null,
                        "emersio": null,
                        "weight": 0,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "ARRO",
                        "cha": "EHN",
                        "loc": "--",
                        "arrival_time": "2021-10-06T06:33:36.440Z",
                        "isc_code": "S",
                        "firstmotion": null,
                        "emersio": null,
                        "weight": 1,
                        "amplitude": null,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "NRCA",
                        "cha": "HNE",
                        "loc": "--",
                        "arrival_time": "",
                        "isc_code": "",
                        "firstmotion": "",
                        "weight": 4,
                        "amplitude": 2.43,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "NRCA",
                        "cha": "HNN",
                        "loc": "--",
                        "arrival_time": "",
                        "isc_code": "",
                        "firstmotion": "",
                        "weight": 4,
                        "amplitude": 2.85,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "NRCA",
                        "cha": "HHE",
                        "loc": "--",
                        "arrival_time": "",
                        "isc_code": "",
                        "firstmotion": "",
                        "weight": 4,
                        "amplitude": 2.51,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "NRCA",
                        "cha": "HHN",
                        "loc": "--",
                        "arrival_time": "",
                        "isc_code": "",
                        "firstmotion": "",
                        "weight": 4,
                        "amplitude": 3.11,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "MTRA",
                        "cha": "EHE",
                        "loc": "--",
                        "arrival_time": "",
                        "isc_code": "",
                        "firstmotion": "",
                        "weight": 4,
                        "amplitude": 0.282,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "MTRA",
                        "cha": "EHN",
                        "loc": "--",
                        "arrival_time": "",
                        "isc_code": "",
                        "firstmotion": "",
                        "weight": 4,
                        "amplitude": 0.23,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "LNSS",
                        "cha": "HHE",
                        "loc": "--",
                        "arrival_time": "",
                        "isc_code": "",
                        "firstmotion": "",
                        "weight": 4,
                        "amplitude": 0.1281,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "LNSS",
                        "cha": "HHN",
                        "loc": "--",
                        "arrival_time": "",
                        "isc_code": "",
                        "firstmotion": "",
                        "weight": 4,
                        "amplitude": 0.1798,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "GAVE",
                        "cha": "EHE",
                        "loc": "--",
                        "arrival_time": "",
                        "isc_code": "",
                        "firstmotion": "",
                        "weight": 4,
                        "amplitude": 0.1476,
                        "ampType": "1"
                    },
                    {
                        "net": "IV",
                        "sta": "GAVE",
                        "cha": "EHN",
                        "loc": "--",
                        "arrival_time": "",
                        "isc_code": "",
                        "firstmotion": "",
                        "weight": 4,
                        "amplitude": 0.1352,
                        "ampType": "1"
                    }
                ]
            }
        }';

        $dataOutputStructure = [
            'ewLogo' => [
                'type',
                'module',
                'installation',
                'instance',
                'user',
                'hostname'
            ],
            'ewMessage' => [
                'quakeId',
                'version',
                'originId',
                'originTime',
                'latitude',
                'longitude',
                'depth',
                'nph',
                'nphS',
                'nphtot',
                'nPfm',
                'gap',
                'dmin',
                'rms',
                'e0az',
                'e0dp',
                'e0',
                'e1az',
                'e1dp',
                'e1',
                'e2',
                'erh',
                'erz',
                'Md',
                'reg',
                'labelpref',
                'Mpref',
                'wtpref',
                'mdtype',
                'mdmad',
                'mdwt',
                'ingvQuality',
                'amplitudeMagnitude',
                'numberOfAmpMagWeightCode',
                'medianAbsDiffAmpMag',
                'preferredMagLabel',
                'preferredMag',
                'numberOfPreferredMags',
                'phases' => [
                    '*' => [
                        "sta",
                        "comp",
                        "net",
                        "loc",
                        "Plabel",
                        "Slabel",
                        "Ponset",
                        "Sonset",
                        "Pat",
                        "Sat",
                        "Pres",
                        "Sres",
                        "Pqual",
                        "Squal",
                        "codalen",
                        "codawt",
                        "Pfm",
                        "Sfm",
                        "datasrc",
                        "Md",
                        "azm",
                        "takeoff",
                        "dist",
                        "Pwt",
                        "Swt",
                        "pamp",
                        "codalenObs",
                        "ccntr" => [
                            0,
                            1,
                            2,
                            3,
                            4,
                            5
                        ],
                        "caav" => [
                            0,
                            1,
                            2,
                            3,
                            4,
                            5
                        ],
                        "amplitude",
                        "ampUnitsCode",
                        "ampType",
                        "ampMag",
                        "ampMagWeightCode",
                        "importanceP",
                        "importanceS"
                    ]
                ]
            ]
        ];

        $dataInput__decoded = json_decode($dataInput, true);

        /* Start hyp2000 */
        $response = $this->postJson(route('v2.location.hyp2000'), $dataInput__decoded);
        $response->assertStatus(200);

        /* Check JSON structure */
        $response->assertJsonStructure($dataOutputStructure);
    }
}
