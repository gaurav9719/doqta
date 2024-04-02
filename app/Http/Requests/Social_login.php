<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class Social_login extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {

        $rules = [
            'social_id' =>  'required',
            'login_type' => 'required|in:1',
            'device_type' => 'required|integer|between:1,2',
            'device_token' => 'required|min:10',
        ];

        return  $rules;
    }

    public function messages()

    {
        return [

            'device_type.between' => 'Invalid device type.',
            'login_type.in' => 'Invalid login type.',
            'device_token.min' => 'Invalid device token.',
        ];
    }


    public function failedValidation(Validator $validator)
    {

        throw new HttpResponseException(response()->json([
            'success'   => 422,
            'message'   => $validator->errors()->first(),
        ],422));
    }
}
