<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class LoginUser extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    // public function authorize(): bool
    // {
    //     return false;
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        $rules = [
            'email' => 'required|string', // Ensure param is present and is a string
        ];
        if (strpos($this->email, '@') !== false) {
            $rules['email'] = 'required|email'; // Validate email format
        }

        $rules = [
            'password' => 'required|string',
            'device_type' => 'required|integer|between:1,2',
            'device_token' => 'required|min:10',
        ];

        return  $rules;
    }

    public function messages()

    {
        return [

            'device_type.between' => 'Invalid device type.',
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
