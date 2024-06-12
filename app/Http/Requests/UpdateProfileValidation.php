<?php

namespace App\Http\Requests;

use App\Rules\AdultValidation;
use App\Rules\ExistsInInterest;
use Illuminate\Validation\Rule;
use App\Rules\ExistsInParticipate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class UpdateProfileValidation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        $userId             =   Auth::id();
        return [
            'bio' => 'nullable|regex:/^[a-zA-Z]/|min:20|max:200',
            'user_name' => 'nullable|unique:users,user_name,'.$userId.'|regex:/^\S*$/u',
            'profile'=>'nullable|mimes:jpg,jpeg,png,bmp,tiff',
            'cover'=>'nullable|mimes:jpg,jpeg,png,bmp,tiff',
            'dob' => ['nullable', 'date', 'date_format:m/d/Y', new AdultValidation],
            'gender' => ['nullable', 'integer', 'exists:genders,id'], 
            'pronoun' => ['nullable', 'integer', 'exists:pronouns,id', 
            'ethnicity' => 'nullable', 'nullable', 'exists:ethnicities,id'],
            'reasons' => ['nullable', 'array', new ExistsInParticipate],
            'reasons.*' => ['nullable', 'integer'],
            'interest' => ['nullable', 'array', new ExistsInInterest],
            'interest.*' => ['nullable', 'integer'],
        ];
    }

    public function messages()
    {
        return [

          'pronouns.exists' => "Invalid pronoun", 
          'ethnicity.exists' => 'Invalid ethnicity',
          'reasons.array' => "Invalid data type",
          'interest.array' => "Invalid data type"

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
