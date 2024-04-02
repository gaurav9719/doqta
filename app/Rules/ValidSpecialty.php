<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Specialty;
class ValidSpecialty implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //
        if (is_numeric($value)) {

            $isExist        =   Specialty::where('id', $value)->exists();

            if(!$isExist){

                $fail('The :nvalid specialty.');
            }

        } else {
            // Check if the specialty name exists
            $existingSpecialty      =       Specialty::where('name', $value)->first();
            if (!$existingSpecialty) {
                // If the specialty name doesn't exist, attempt to add it as a new specialty
                $newSpecialty       = Specialty::create(['name' => $value]);

                $newSpecialtyId = $newSpecialty->id;

            }
        }

    }
}
