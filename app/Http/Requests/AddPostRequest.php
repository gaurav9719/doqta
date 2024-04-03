<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
class AddPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules()
    {
        return [
            'title' => 'required|string|min:10|max:200',
            'content' => 'required|string|min:10',
            'media_url' => 'nullable|string|min:10',
            'post_type' => 'required|in:normal,community',
            'post_category' => 'required|integer|between:1,3',
            'community_id' => 'nullable|integer|exists:groups,id',
            'link' => 'nullable|url',
        ];
    }
    
    public function messages()
    {
        return [
            'title.min' => 'The title must be at least :min characters.',
            'title.max' => 'The title may not be greater than :max characters.',
            'content.min' => 'The content must be at least :min characters.',
            'media_url.min' => 'The media URL must be at least :min characters.',
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
