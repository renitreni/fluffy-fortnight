<?php

namespace App\Services\Contracts;

interface MaliciousUrlScanner
{
    /**
     * Determine if the given URL is malicious or unsafe.
     *
     * @param  string  $url  The normalized URL to check.
     * @return bool True if malicious or unsafe, false otherwise.
     */
    public function isMalicious(string $url): bool;
}
