<?php

declare(strict_types=1);

namespace PSR7Csrf\HttpMethod;

use Lcobucci\JWT\Signer;
use Psr\Http\Message\RequestInterface;

interface IsSafeHttpRequestInterface
{
    public function __invoke(RequestInterface $request) : bool;
}
