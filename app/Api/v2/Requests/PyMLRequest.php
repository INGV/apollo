<?php

namespace App\Api\v2\Requests;

use Illuminate\Foundation\Http\FormRequest;
use VLauciani\LaravelValidationRules\Rules\RFC3339ExtendedRule;

class PyMLRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->sometimes('data.phases.*.arrival_time', new RFC3339ExtendedRule(), function ($input, $item) {
            return isset($item->isc_code);
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $validator_default_check = config('apollo.validator_default_check');

        $validationRules = [
            'data'                                                  => ['required', 'array'],
            'data.pyml_conf'                                        => ['required', 'array'],

            /* pyml_conf.preconditions */
            'data.pyml_conf.preconditions'                          => ['required', 'array'],
            'data.pyml_conf.preconditions.theoretical_p'            => ['required', 'boolean'],
            'data.pyml_conf.preconditions.theoretical_s'            => ['required', 'boolean'],
            'data.pyml_conf.preconditions.delta_corner'             => ['required', 'numerical'],
            'data.pyml_conf.preconditions.max_lowcorner'            => ['required', 'numerical'],

            /* pyml_conf.station_magnitude */
            'data.pyml_conf.station_magnitude'                      => ['required', 'array'],
            'data.pyml_conf.station_magnitude.delta_peaks'          => ['required', 'numerical'],
            'data.pyml_conf.station_magnitude.use_stcorr_hb'        => ['required', 'boolean'],
            'data.pyml_conf.station_magnitude.use_stcorr_db'        => ['required', 'boolean'],
            'data.pyml_conf.station_magnitude.when_no_stcorr_hb'    => ['required', 'boolean'],
            'data.pyml_conf.station_magnitude.when_no_stcorr_db'    => ['required', 'boolean'],

            /* pyml_conf.event_magnitude */
            'data.pyml_conf.event_magnitude'                        => ['required', 'array'],
            'data.pyml_conf.event_magnitude.mindist'                => ['required', 'boolean'],
            'data.pyml_conf.event_magnitude.maxdist'                => ['required', 'boolean'],
            'data.pyml_conf.event_magnitude.hm_cutoff'              => ['required', 'array'],
            'data.pyml_conf.event_magnitude.outliers_max_it'        => ['required', 'numerical'],
            'data.pyml_conf.event_magnitude.outliers_red_stop'      => ['required', 'numerical'],
            'data.pyml_conf.event_magnitude.outliers_nstd'          => ['required', 'numerical'],
            'data.pyml_conf.event_magnitude.outliers_cutoff'        => ['required', 'numerical'],

            'data.origin'                                           => ['required', 'array'],
            'data.origin.lat'                                       => $validator_default_check['lat'],
            'data.origin.lon'                                       => $validator_default_check['lon'],
            'data.origin.depth'                                     => $validator_default_check['depth'],
            'data.amplitudes.*.sta'                                 => $validator_default_check['sta'],
            'data.amplitudes.*.net'                                 => $validator_default_check['net'],
            'data.amplitudes.*.cha'                                 => $validator_default_check['cha'],
            'data.amplitudes.*.loc'                                 => $validator_default_check['loc'],
            'data.amplitudes.*.elev'                                => ['numeric', 'min:-100000', 'max:100000'],
            'data.amplitudes.*.amp1'                                => ['required', 'numeric'],
            'data.amplitudes.*.time1'                               => ['required', new RFC3339ExtendedRule()],
            'data.amplitudes.*.amp2'                                => ['required', 'numeric'],
            'data.amplitudes.*.time2'                               => ['required', new RFC3339ExtendedRule()],
        ];

        return $validationRules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'in' => 'The :attribute must be :values.',
        ];
    }
}
