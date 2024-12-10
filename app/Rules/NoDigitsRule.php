<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NoDigitsRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Define a stricter regex pattern for fields to not contain any digits
        $pattern = '/^[^0-9]*$/';

        // Check if the attribute matches the pattern
        if (!preg_match($pattern, $value)) {
            $attribute_de = AttributeHelper::get($attribute);
            $fail(
                str_replace(':attribute_de', $attribute_de, ':attribute_de darf keine Ziffern enthalten.')
            );
        }
    }
}
