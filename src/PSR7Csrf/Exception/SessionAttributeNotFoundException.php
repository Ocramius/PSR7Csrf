<?php

declare(strict_types=1);

namespace PSR7Csrf\Exception;

use Psr\Http\Message\ServerRequestInterface;
use UnexpectedValueException;

class SessionAttributeNotFoundException extends UnexpectedValueException implements ExceptionInterface
{
    public static function fromAttributeNameAndRequest(string $attributeName, ServerRequestInterface $request) : self
    {
        return new self(sprintf(
            'Provided request contains no matching session attribute "%s", attributes %s exist',
            $attributeName,
            json_encode(array_keys($request->getAttributes()))
        ));
    }
}
