<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;


class AdultValidation implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //https://amanj0314.medium.com/exploring-the-power-of-custom-rule-validation-in-laravel-10-86c9c5abd470

         $date = Carbon::parse($value);
       // $date   = Carbon::createFromFormat('m/d/Y', $value);

        if ($date->isPast() && $date->diffInYears(now()) < 18)  {
        
            $fail("User must be above 18 years");

        }
    }

}
