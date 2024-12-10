<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PasswordRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Define a stricter regex pattern for valid password to be min 8 characters
        $pattern = '/^.{8,}$/';

        // Check if the attribute matches the pattern
        if (!preg_match($pattern, $value)) {
            $attribute_de = AttributeHelper::get($attribute);
            $fail(
                str_replace(':attribute_de', $attribute_de, 'Das :attribute_de muss mindestens 8 Zeichen lang sein.')
            );
        }
    }
}
