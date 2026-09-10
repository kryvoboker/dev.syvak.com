<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

final class ValidRegexMask implements ValidationRule
{
    /**
     * @param string  $attribute
     * @param mixed   $value
     * @param Closure $fail
     *
     * @return void
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $value = Str::trim(is_scalar($value) ? (string) $value : '');

        if (Str::length($value) > 1000 || @preg_match($value, '') === false) {
            $fail('admin/settings/contacts_page_settings.errors.invalid_regex');
        }
    }
}
