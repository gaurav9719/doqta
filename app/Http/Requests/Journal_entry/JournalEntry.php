<?php

namespace App\Http\Requests\Journal_entry;

use App\Rules\SymptomIsExist;
use App\Rules\AtLeastOneSymptom;
use App\Rules\FeelingTypeIsExist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class JournalEntry extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules()
    {
        return [

            'journal_id'=>'required|integer|exists:journals,id',
            'feeling'=>'required|integer|exists:feelings,id',
            'feeling_type' => ['required','array',new FeelingTypeIsExist],
            'pain' => 'required|integer|between:0,5',
            'symptom'=>['nullable','array',new SymptomIsExist],
            'extra_symptom'=>['nullable','array'],
            'content' => 'required|string|min:3',
            'link' => 'nullable|url',
            'media' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
            // 'audio' => 'nullable|file|mimes:mpeg,wav,mp3|max:9048',
            'audio' => 'nullable|file|mimes:application/octet-stream,audio/mpeg,mpga,mp3,wav,3gp,mp4a,m4a,aac,mpga|max:9048',
        ];
    }

   
 


    public function messages()
    {
        return [
            'feeling.integer' => 'Invalid feeling',
            'feeling_type.array' => 'Invalid feeling type',
            'pain.*' => 'Invalid pain',
            'symptom.array' => 'Invalid symptom',
            'other_symptom.array' => 'Invalid other symptom',
            'symptom.required_without' => 'Either symptom or other_symptom must be present.',
            'other_symptom.required_without' => 'Either symptom or other_symptom must be present.',
        
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
