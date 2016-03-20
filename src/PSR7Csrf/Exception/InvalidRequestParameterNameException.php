<?php

declare(strict_types=1);

namespace PSR7Csrf\Exception;

use InvalidArgumentException;

class InvalidRequestParameterNameException extends InvalidArgumentException implements ExceptionInterface
{
    public static function fromEmptyRequestParameterName() : self
    {
        return new self('The given request parameter must be a non-empty string');
    }
}
