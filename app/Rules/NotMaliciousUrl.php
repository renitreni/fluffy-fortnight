<?php

namespace App\Rules;

use App\Services\Contracts\MaliciousUrlScanner;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NotMaliciousUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var MaliciousUrlScanner $scanner */
        $scanner = app(MaliciousUrlScanner::class);

        if ($scanner->isMalicious((string) $value)) {
            $fail('The provided URL has been flagged as malicious or unsafe.');
        }
    }
}
