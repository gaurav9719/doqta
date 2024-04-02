<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\user;
use App\Models\ParticipantCategory;

class ExistsInParticipate implements ValidationRule
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
        $true   =   ParticipantCategory::whereIn('id', $value)->count() === count($value);
        
        if(!$true){

            $fail ('The :invalid category.');

        }
    }

    public function message()
    {
        return 'One or more provided user IDs do not exist in the users table.';
    }
}
