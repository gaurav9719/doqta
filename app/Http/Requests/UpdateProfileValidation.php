<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;


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
            'user_name' => 'nullable|unique:users,user_name,'.$userId,
            'profile'=>'nullable|mimes:jpg,jpeg,png,bmp,tiff',
            'cover'=>'nullable|mimes:jpg,jpeg,png,bmp,tiff',

        ];
    }

    public function messages()
    {
        return [

          
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
