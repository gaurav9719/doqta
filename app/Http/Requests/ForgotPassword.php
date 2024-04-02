<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
class ForgotPassword extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules()
    {
        return [
            //
            'email' => 'required|email|exists:users,email',
            'type' => 'required|integer|between:1,4',
        ];
    }

    public function messages()      //OPTIONAL
    {
        return [
            'email.exists' => 'Account does not exist',
            'type.between' => 'Invalid request!',
        ];
    }

    public function failedValidation(Validator $validator)
    {

        throw new HttpResponseException(response()->json([
            'success'   => 400,
            'message'   => validationErrorsToString($validator->errors())
        ],400));
    }
}
