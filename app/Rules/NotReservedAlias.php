<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NotReservedAlias implements ValidationRule
{
    /**
     * The list of reserved words that cannot be used as custom aliases.
     *
     * @var array<string>
     */
    protected array $reservedWords = [
        'api',
        'admin',
        'login',
        'health',
        'swagger',
        'register',
        'dashboard',
        'links',
    ];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array(strtolower((string) $value), $this->reservedWords, true)) {
            $fail('The custom alias is a reserved word and cannot be used.');
        }
    }
}
