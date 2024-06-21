<?php

namespace App\Rules;

use Closure;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;

class UserExists implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //

        if (is_array($value)) {

            foreach ($value as $id) {

                if (!User::find($id)) {

                    $fail('Invalid user.');
                }
            }
        }

        $isExistUser        =   User::where('id',$value)->exists();
        if(!$isExistUser){

            $fail ('Invalid user');

        }
    }
}
