<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class UserPreferenceValidation extends FormRequest
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
            //
            'distance' => 'required|numeric',
            'age_preference' => 'required|numeric|between:18,100',
            'gender_preference' => 'required|numeric|between:0,2',
            'ghost_coach'=>'required|numeric|between:1,2',
        ];
    }

    public function messages()

    {
        return [
            'age_preference.between' => 'Please choose the 18+ age range',
            'gender_preference.between' => 'Please choose gender preference.',
            'ghost_coach.between' => 'Please choose Yes or No',
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
