<?php

declare(strict_types=1);

namespace PSR7Csrf\RequestParameter;

use Psr\Http\Message\ServerRequestInterface;

interface ExtractCSRFParameterInterface
{
    public function __invoke(ServerRequestInterface $request) : string;
}
