<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule; 
class AddPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules()
    {
        // return [
        //     'media' => 'nullable|file|mimes:jpeg,png,mp4,mpeg,mp4,wav|max:2048', 
        //     'media_type' => 'required_if:media,!=,""|integer|between:1,3',

        //     'title' => 'required|string|min:10|max:200',
        //     'content' => 'required|string|min:10',
        //     'post_type' => 'required|in:normal,community',
        //     'post_category' => 'required|integer|between:1,3',
        //     'community_id' => 'required|integer|exists:groups,id',
        //     'link' => 'nullable|url',
        // ];
        return [
            'title' => ['required', 'string', 'min:1'],
            'content' => ['required', 'string', 'min:10','max:3000'],
            'post_type' => ['nullable', 'in:normal,community'],
            'post_category' => ['nullable', 'integer', 'between:1,3'], //1: seeing advice, 2: giving advice, 3: sharing media	
            'lat' => ['nullable', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'long' => ['nullable', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
            'community_id' => ['required_if:post_type,community', 'integer', 'exists:groups,id'],
            'link' => ['nullable', 'url'],
            'wrote_by' => ['nullable','integer'],
            'media_type' => 'required|between:0,4',
            'media' => [
                'required_if:media_type,1,2,3', // Media is required if media_type is 1, 2, or 3
                'file', // Must be a file upload
                function ($attribute, $value, $fail) {
                    $allowedMimeTypes = [];
    
                    if ($this->media_type == 1) { // For image types

                        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp'];

                    } elseif ($this->media_type == 2) { // For video types

                        $allowedMimeTypes = [
                            'video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo',
                            'video/x-flv', 'video/x-matroska', 'video/webm', 'video/x-ms-wmv'
                        ];

                    } elseif ($this->media_type == 3) { // For audio types

                        $allowedMimeTypes = [
                            'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/ogg',
                            'audio/aac', 'audio/flac', 'audio/midi', 'audio/x-ms-wma'
                        ];
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
            'title.min' => 'The title must be at least :min characters.',
            'content.min' => 'The content must be at least :min characters.',
            // 'media_url.min' => 'The media URL must be at least :min characters.',
            'user_id.required' => 'The user ID field is required.',
            'user_id.integer' => 'The user ID must be an integer.',
            'user_id.exists' => 'The selected user ID is invalid.',
            'post_type.in' => 'The post type must be either "normal" or "community".',
            'post_category.between' => 'The post category must be between :min and :max.',
            'community_id.integer'=>"Invalid community id"
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
