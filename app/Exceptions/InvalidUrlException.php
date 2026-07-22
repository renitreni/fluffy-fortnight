<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a submitted URL fails validation or normalization.
 *
 * This exception is used by UrlNormalizerService to signal that a URL
 * cannot be processed. The message is safe to surface to end-users.
 * HTTP status 422 is appropriate for form validation failures.
 */
class InvalidUrlException extends RuntimeException
{
    /**
     * The recommended HTTP status code for this exception.
     *
     * @var int
     */
    public int $httpStatus = 422;

    /**
     * Create a new InvalidUrlException.
     *
     * @param string $message  Human-readable description of the validation failure.
     * @param int    $code     Optional PHP exception code (not the HTTP status).
     */
    public function __construct(string $message = 'The provided URL is invalid.', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
