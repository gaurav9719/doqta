<?php

namespace App\Rules;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\ValidationRule;

class ChatActiveUser implements ValidationRule
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
        // Check if all user IDs exist and are active
        $count      = DB::table('users')
                        ->whereIn('id', $value)
                        ->where('is_active', 1)
                        ->count();

        $true       =   $count === count($value);

        if(!$true){

            $fail ('The :invalid user.');

        }
    }

    public function message()
    {
        return 'One or more provided user IDs do not exist in the users table.';
    }
}
