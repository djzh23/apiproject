<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Propaganistas\LaravelPhone\PhoneNumber;


class PhoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Use the PhoneNumber class from Laravel-Phone to validate the phone number
        $phone = new PhoneNumber($value);

        // Check if the phone number is valid (e.g., not empty and matches a known format)
        if (!$phone->isValid()) {
            $attribute_de = AttributeHelper::get($attribute);
            $fail(
                str_replace(':attribute_de', $attribute_de, 'Die :attribute_de muss ein gültiger Rufnummer sein.')
            );
        }
    }
}
