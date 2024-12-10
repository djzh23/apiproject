<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PostalCodeRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Define a stricter regex pattern for valid postal codes internationally
        $pattern = '/^[A-Za-z0-9\- ]{3,10}$/';

        // Check if the attribute matches the pattern
        if (!preg_match($pattern, $value)) {
            $attribute_de = AttributeHelper::get($attribute);
            $fail(
                str_replace(':attribute_de', $attribute_de, 'Die :attribute_de muss gültig sein.')
            );
        }
    }
}
