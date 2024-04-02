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
            'bio' => 'required|regex:/^[a-zA-Z]/|min:50|max:160',
            'user_name' => 'required|unique:users,user_name,'.$userId,
            'profile'=>'required|mimes:jpg,jpeg,png,bmp,tiff',
            'cover'=>'required|mimes:jpg,jpeg,png,bmp,tiff',

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
