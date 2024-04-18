<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;


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
    
            case 2:
            case 3:
            case 4:
                $allowedMimeTypes = ['file'];
                break;
    
            default:
                Log::error('Error caught: Invalid message type ' . $this->message_type);
                throw new \Exception('Invalid message type ' . $this->message_type);
        }
    
        return [
            'receiver_id' => 'required|exists:users,id',
            'message_type' => 'required|numeric|between:1,7',
            'media' => [
                'required_if:message_type,2,3,4,5,7',
                'file',
                Rule::requiredIf(function () {
                    Log::error('Error caught: "message_type" ' . $this->message_type);
    
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
    // public function rules(): array
    // {
    //     return [
    //         'receiver_id' => 'required|exists:users,id',
    //         'message_type' => 'required|numeric|between:1,6',
    //         'media'=>'required_if:(message_type,==,2 OR message_type,==,3 OR message_type,==,4 OR message_type,==,5)|file',
    //         'file' => [
    //             'required',
    //             Rule::requiredIf(function () {
    //                 Log::error('Error caught: "type" ' . $this->message_type);

    //                 return in_array($this->type_id, [2, 3, 6]); // Check if type_id is 2, 3, or 6
    //             }),

    //             function ($attribute, $value, $fail) {
    //                 $allowedMimeTypes = [];
                    
    //                 if ($this->type_id == 2) { // For audio types
    //                     $allowedMimeTypes = ['audio/mpeg', 'audio/wav'];
                        
    //                 } elseif ($this->type_id == 3) { // For video types
    //                     $allowedMimeTypes = ['video/mp4', 'video/mpeg'];
    //                 } elseif ($this->type_id == 6) { // For image types
    //                     $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp'];

    //                 }
                    
    //                 // Check if the uploaded file MIME type is in the allowedMimeTypes array
    //                 if (!in_array($value->getMimeType(), $allowedMimeTypes)) {
    //                     $fail('The ' . $attribute . ' must be a valid file of the specified type.');
    //                 }
    //             },
    //         ],

    //     ];
    // }

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
