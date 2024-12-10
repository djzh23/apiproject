<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class StringLengthRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Define a stricter regex pattern for fields to be less than 255 characters
        $pattern = '/^.{0,255}$/';

        // Check if the attribute length matches the pattern
        if (!preg_match($pattern, $value)) {
            $attribute_de = AttributeHelper::get($attribute);
            $fail(
                str_replace(':attribute_de', $attribute_de, ':attribute_de darf nicht länger als 255 Zeichen sein.')
            );
        }
    }
}
