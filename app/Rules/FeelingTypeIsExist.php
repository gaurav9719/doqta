<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\FeelingType;

class FeelingTypeIsExist implements ValidationRule
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
        $true   =   FeelingType::whereIn('id', $value)->count() === count($value);
        
        if(!$true){

            $fail ('The :invalid feeling.');

        }
    }

    public function message()
    {
        return 'One or more provided feeling types do not exist.';
    }
}
