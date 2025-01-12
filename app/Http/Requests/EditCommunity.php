<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class EditCommunity extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules()
    {
        return [
            //
            'id'=>'required|integer|exists:groups,id',
            'name' => 'nullable|min:1|max:25',
            'description' => 'nullable|min:20|max:200',
            'cover_photo'=>'nullable|mimes:jpg,jpeg,png,bmp,tiff',
            ];
    }

    public function messages()      //OPTIONAL
    {
        return [
            
            'id.interger'=>"Invalid community",
            'name.min' => 'Names must have a minimum of 1 characters',
            'name.max' => 'Names must have a maximum of  25 characters',
            'description.min' => 'description must have a minimum of  20 characters',
            'name.regex' => 'Characters and numerals are both accepted.',
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
