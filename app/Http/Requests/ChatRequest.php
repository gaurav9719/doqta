<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class ChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
  
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receiver_id' => 'required|exists:users,id',
            'message_type' => 'required|numeric|between:1,6',
            'media'=>'required_if:(message_type,==,2 OR message_type,==,3 OR message_type,==,4 OR message_type,==,5)|file',
            'file' => [
                'required',
                Rule::requiredIf(function () {

                    return in_array($this->type_id, [2, 3, 6]); // Check if type_id is 2, 3, or 6
                }),

                function ($attribute, $value, $fail) {
                    $allowedMimeTypes = [];
                    
                    if ($this->type_id == 2) { // For audio types
                        $allowedMimeTypes = ['audio/mpeg', 'audio/wav'];
                        
                    } elseif ($this->type_id == 3) { // For video types
                        $allowedMimeTypes = ['video/mp4', 'video/mpeg'];
                    } elseif ($this->type_id == 6) { // For image types
                        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp'];

                    }
                    
                    // Check if the uploaded file MIME type is in the allowedMimeTypes array
                    if (!in_array($value->getMimeType(), $allowedMimeTypes)) {
                        $fail('The ' . $attribute . ' must be a valid file of the specified type.');
                    }
                },
            ],

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
