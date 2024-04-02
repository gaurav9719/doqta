<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Interest;
class ExistsInInterest implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //
        $true   =   Interest::whereIn('id', $value)->count() === count($value);
        
        if(!$true){

            $fail ('The :invalid interest.');

        }
    }
}
