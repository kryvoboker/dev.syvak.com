<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

final class ValidRegexMask implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $value = Str::trim((string) $value);

        if (Str::length($value) > 1000 || @preg_match($value, '') === false) {
            $fail('admin/settings/contacts_page_settings.errors.invalid_regex');
        }
    }
}
