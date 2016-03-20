<?php

declare(strict_types=1);

namespace PSR7Csrf\Exception;

use InvalidArgumentException;

class InvalidExpirationTimeException extends InvalidArgumentException implements ExceptionInterface
{
    public static function fromInvalidExpirationTime(int $expirationTime) : self
    {
        return new self(sprintf('The provided expiration time %s is invalid: expected a >0 integer', $expirationTime));
    }
}
