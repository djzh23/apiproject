<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class StrictEmailRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Define a stricter regex pattern for email validation
        $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

        if (strlen($value) > 255) {
            $fail(__('messages.validation.email.max_length'));
        }
        // Check if the email matches the pattern
        if (!str_contains($value, '@') || !preg_match($pattern, $value) ) {
            $fail( __('messages.validation.email.invalid'));
        }
    }
}
