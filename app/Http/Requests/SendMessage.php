<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class SendMessage extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        return [
            //
            'receiver_id' => 'required|integer|exists:users,id',
            'message_type' => 'required|integer|between:1,6',
            'media'=>'required_if:(message_type,==,2 OR message_type,==,3 OR message_type,==,6)|file',
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
            'password.regex' => 'Password must contain at least one number and both uppercase and lowercase letters and special symbol.',
            'user_role.between' => 'Invalid user role.',
            'device_type.between' => 'Invalid device type.',
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
