<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Rules\FeelingTypeIsExist;
use App\Rules\SymptomIsExist;
class JournalValidation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules()
    {
        return [
            'title' => 'required|string|min:2|max:100',
            'topic' => 'nullable|integer|exists:journal_topics,id',
            'other_topic' => 'nullable|string',
            'writing_for' => 'required|string|min:3',
            'color' => 'required|integer|exists:colors,id',
        ];

        // return [
        //     'title' => 'required|string|min:10|max:100',
        //     'topic' => 'required|integer|exists:journal_topics,id',
        //     'feeling'=>'required|integer|exists:feelings,id',
        //     'writing_for' => 'required|string|min:3',
        //     'color' => 'required|integer|exists:colors,id',
        //     'feeling_type' => ['required','array',new FeelingTypeIsExist],
        //     'pain' => 'required|integer|between:0,5',
        //     'symptom'=>['required','array',new SymptomIsExist],
        //     'other_symptom'=>['required','array'],
        //     'content' => 'required|string|min:3',
        //     'link' => 'nullable|url',
        //     'media' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
        //     'audio' => 'nullable|file|mimes:mpeg,wav,mp3|max:9048',
        // ];
    }
 


    public function messages()
    {
        return [
            'title.min' => 'The title must be at least :min characters.',
            'title.max' => 'The title may not be greater than :max characters.',
            'writing_for.min' => 'The writing must be at least :min characters.',
            'color.integer'=>"Invalid color",
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
