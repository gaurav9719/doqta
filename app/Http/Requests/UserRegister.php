<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Rules\AdultValidation;
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
        
            'name' => 'required|regex:/^[a-zA-Z]+$/u|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|string|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'country_code'=>'required|numeric|regex:/^\d{1,3}$/',
            'phone_no'=>'required|numeric|digits_between:10,15|not_regex:/[a-z]/|unique:users',
            'dob'=>['required','date','date_format:Y-m-d',new AdultValidation],
            'user_role' => 'required|integer|between:2,3', // 2 dater,3 recruiter
            'device_type' => 'required|integer|between:1,2',
            'gender' => 'required|integer|between:1,2',
            'device_token' => 'required',
            'zip_code' => 'required',
            'reference_code'=>'nullable|exists:users,reference_code',
            'lat' => 'required|regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/',
            'long' => 'required|regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/',

        ];
    }

    public function messages()

    {
        return [
            'password.regex' => 'Password must contain at least one number and both uppercase and lowercase letters and special symbol.',
            'name.regex' => 'Only letters are allowed in name.',
            'user_role.between' => 'Invalid user role.',
            'device_type.between' => 'Invalid device type.',
            'phone_no.digits_between' => 'Invalid phone number.',
            'phone_no.unique' => 'There is already another account with this phone number',
            'lat.regex' => 'Latitude value appears to be incorrect format.',
            'long.regex' => 'Longitude value appears to be incorrect format.'
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
