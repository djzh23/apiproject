<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BicRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pattern = '/^[A-Z]{4}[A-Z]{2}[0-9A-Z]{1,2}([0-9A-Z]{3})?$/';

        // Check if the BIC matches the pattern
        if (!preg_match($pattern, $value)) {
            $attribute_de = AttributeHelper::get('bic');
            $fail(
                str_replace(':attribute_de', $attribute_de, 'The :attribute_de muss ein gültiger BIC sein.')
            );
        }

    }
}
