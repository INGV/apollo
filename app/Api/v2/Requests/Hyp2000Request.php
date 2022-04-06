<?php

namespace App\Api\v2\Requests;

use Illuminate\Foundation\Http\FormRequest;
use VLauciani\LaravelValidationRules\Rules\RFC3339ExtendedRule;

class Hyp2000Request extends FormRequest
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
        $validationRules = [
            'data'                              => ['required', 'array'],
            'data.hyp2000_conf'                 => ['required', 'array'],
            'data.model'                        => ['required', 'array'],
            'data.output'                       => ['nullable', 'string', 'in:prt,sum,arc,json'],
            'data.phases.*.sta'                 => ['required', 'string'],
            'data.phases.*.net'                 => ['string', 'between:1,2'],
            'data.phases.*.cha'                 => ['string', 'size:3'],
            'data.phases.*.loc'                 => ['nullable', 'string'],
            //'data.phases.*.arrival_time'        => ['required_with:isc_code', 'date_format:Y-m-d\TH:i:s.vP'],
            'data.phases.*.isc_code'            => ['nullable', 'string', 'not_in:"0"', 'min:1', 'max:8'],
            'data.phases.*.firstmotion'         => ['nullable', 'string', 'size:1', 'in:U,D'],
            'data.phases.*.emersio'             => ['nullable', 'string', 'size:1', 'in:E,I'],
            'data.phases.*.weight'              => ['nullable', 'integer'],
            'data.phases.*.amplitude'           => ['nullable', 'numeric'],
            'data.phases.*.ampType'             => ['nullable', 'integer'],
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
