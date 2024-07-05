<?php

namespace App\Http\Requests;

use App\Rules\AdultValidation;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRegister extends FormRequest
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
        return [
            // "^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$"
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'string',
                Password::min(8)  // Minimum length of 8 characters
                    ->mixedCase()  // Must contain both upper and lower case letters
                    ->numbers()    // Must contain at least one number
                    ->symbols()    // Must contain at least one special character
                   ],
            'device_type' => 'required|integer|between:1,2',
            'device_token' => 'required|min:10',
            'lat' => ['nullable', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'long' => ['nullable', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],

        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'The email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            // 'password.regex' => 'Password must contain at least one number and both uppercase and lowercase letters and special symbol.',
            'device_type.between' => 'Invalid device type.',
            'lat.regex' => 'Latitude value appears to be incorrect format.',
            'long.regex' => 'Longitude value appears to be incorrect format.',
            'device_token.min'=>"Invalid device token"
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
