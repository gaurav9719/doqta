<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AtLeastOneSymptom implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    { 
        $data = request()->all();
        dd($data);
        $symptom = isset($data['symptom']) && is_array($data['symptom']) && !empty($data['symptom']);
        $otherSymptom = isset($data['other_symptom']) && is_array($data['other_symptom']) && !empty($data['other_symptom']);

        if (!$symptom && !$otherSymptom) {
            $fail('Either symptom or other_symptom must be present.');
        }
    }
}
