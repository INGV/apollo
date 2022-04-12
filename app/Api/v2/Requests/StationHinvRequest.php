<?php

namespace App\Api\v2\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StationHinvRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $validationRules = [
            'sta'                 => ['required', 'string'],
            'net'                 => ['required', 'string', 'between:1,2'],
            'cha'                 => ['required', 'string', 'size:3'],
            'loc'                 => ['nullable', 'string'],
            'cache'               => ['in:true,false'],
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
