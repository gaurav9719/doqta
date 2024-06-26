<?php

namespace App\Http\Requests\GroupRequest;

use App\Rules\UserExists;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class GroupRequestValidation extends FormRequest
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
        $allowedMimeTypes = [];

        switch ($this->message_type) {
            case 1:
                // No validation for text messages
                break;

            case 2:
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp'];
                break;

            case 3:
                $allowedMimeTypes = ['audio/mpeg', 'audio/wav'];
                break;

            case 4:
                $allowedMimeTypes = ['video/mp4', 'video/mpeg'];
                break;

            case 5:
                // No validation for location messages
                break;

            case 6:
                // No validation for contact_share messages
                break;

            case 7:
                // No validation for document_share messages
                break;

            default:
                // Handle invalid message type
                return [
                    'message_type' => [
                        'required',
                        'integer',
                        'between:1,7',
                        function ($attribute, $value, $fail) {
                            $fail('Invalid message type ' . $value);
                        },
                    ],
                ];
        }

        return [

            'type'=>'required|integer|between:1,2',
            'receiver_id' => 'required|string',
            'message_type' => 'required|integer|between:1,7',
            'message'=> 'required_if:message_type,1',
            'media' => [
                'required_if:message_type,2,3,4,5,7',
                'file',
                Rule::requiredIf(function () {

                    return in_array($this->message_type, [2, 3, 4, 7]); // Check if message_type is 2, 3, 4, or 7
                }),

                function ($attribute, $value, $fail) use ($allowedMimeTypes) {
                    
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

            'message.message_type'=>"Message required"
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
