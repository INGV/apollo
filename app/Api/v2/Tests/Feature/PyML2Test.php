<?php

namespace App\Api\v2\Tests\Feature;

use App\Apollo\Traits\UtilsTrait;
use Tests\TestCase;

class PyML2Test extends TestCase
{
    use UtilsTrait;

    protected $output_array_from_swagger;

    protected $structure__output_array_from_swagger;

    protected $input_json = '{
        "data": {
            "pyml_conf": {
                "preconditions": {
                    "theoretical_p": true,
                    "theoretical_s": true,
                    "delta_corner": 5,
                    "max_lowcorner": 15
                },
                "station_magnitude": {
                    "station_magnitude": "meanamp",
                    "amp_mean_type": "geo",
                    "delta_peaks": 1,
                    "use_stcorr_hb": true,
                    "use_stcorr_db": true,
                    "when_no_stcorr_hb": true,
                    "when_no_stcorr_db": true,
                    "mag_mean_type": "meanamp"
                },
                "event_magnitude": {
                    "mindist": 10,
                    "maxdist": 600,
                    "hm_cutoff": [
                        0.3,
                        1
                    ],
                    "outliers_max_it": 10,
                    "outliers_red_stop": 0.05,
                    "outliers_nstd": 1,
                    "outliers_cutoff": 0.1
                }
            },
            "origin": {
                "lat": 39.91999,
                "lon": 15.98228,
                "depth": 6.37
            },
            "amplitudes": []
        }
    }';

    /**
     * This outoput JSON must be the same used in the Swaggewr documentation: 'POST:/location/v2/pyml'
     */
    protected $output_json_from_swagger = '{
        "type": "about:blank",
        "title": "Unprocessable Content",
        "status": 422,
        "detail": "The data.amplitudes field is required.",
        "instance": "http://127.0.0.1:8586/api/location/v2/pyml",
        "version": null,
        "request_submitted": "2023-10-20T13:10:26 UTC",
        "errors": {
            "data.amplitudes": [
                "The data.amplitudes field is required."
            ]
        }
    }';

    public function test_pyml_amplitude_array_missing()
    {
        /* Convert '$output_json_from_swagger' to array */
        $this->output_array_from_swagger = json_decode($this->output_json_from_swagger, true);

        /* Get array structure from '$output_array_from_swagger' */
        $this->structure__output_array_from_swagger = UtilsTrait::getArrayStructure($this->output_array_from_swagger);

        $input_array = json_decode($this->input_json, true);

        /* Start pyml */
        $response = $this->postJson(route('v2.location.pyml'), $input_array);
        //$response->dump();
        $response->assertStatus(422);

        /* Check JSON structure */
        $response->assertJsonStructure($this->structure__output_array_from_swagger);
    }
}
