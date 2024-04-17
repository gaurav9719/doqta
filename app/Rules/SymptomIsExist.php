<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\journalSymptoms;
use App\Models\PhysicalSymptom;

class SymptomIsExist implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //
        if (!is_array($value)) {
            $fail('The :attribute must be an array.');
            return;
        }
        $true   =   PhysicalSymptom::whereIn('id', $value)->count() === count($value);
        
        if(!$true){

            $fail ('The :invalid symptom.');

        }
    }

    public function message()
    {
        return 'One or more provided sympton do not exist.';
    }
}
