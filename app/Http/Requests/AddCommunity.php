<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
class AddCommunity extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules()
    {
        return [
            //
            'name' => 'required|regex:/^[a-zA-Z\s]+$/u|min:3|max:25',
            'description' => 'required|min:10|max:200',
            'cover_photo'=>'required|mimes:jpg,jpeg,png,bmp,tiff',
            ];
    }

    public function messages()      //OPTIONAL
    {
        return [

            'name.min' => 'Names must have a minimum of 3 characters',
            'name.max' => 'Names must have a maximum of  200 characters',
            'name.regex' => 'Only characters are allowed',
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
