<?php

declare(strict_types=1);

namespace PSR7Csrf\RequestParameter;

use Psr\Http\Message\ServerRequestInterface;
use PSR7Csrf\Exception\InvalidRequestParameterNameException;

final class ExtractCSRFParameter implements ExtractCSRFParameterInterface
{
    /**
     * @var string
     */
    private $csrfDataKey;

    public function __construct(string $csrfDataKey)
    {
        if ('' === $csrfDataKey) {
            throw InvalidRequestParameterNameException::fromEmptyRequestParameterName();
        }

        $this->csrfDataKey = $csrfDataKey;
    }

    public function __invoke(ServerRequestInterface $request) : string
    {
        /* @var $requestBody array */
        $requestBody = $request->getParsedBody();

        if (is_object($requestBody) && array_key_exists($this->csrfDataKey, (array) $requestBody)) {
            $arrayBody = (array) $requestBody;

            return $this->ensureThatTheValueIsAString($arrayBody[$this->csrfDataKey]);
        }

        if (is_array($requestBody) && array_key_exists($this->csrfDataKey, $requestBody)) {
            return $this->ensureThatTheValueIsAString($requestBody[$this->csrfDataKey]);
        }

        return '';
    }

    private function ensureThatTheValueIsAString($value) : string
    {
        if (! is_string($value)) {
            return '';
        }

        return $value;
    }
}
