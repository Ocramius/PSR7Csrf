<?php

declare(strict_types=1);

namespace PSR7Csrf;

use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token;
use Psr\Http\Message\ServerRequestInterface;

interface TokenGeneratorInterface
{
    public function __invoke(ServerRequestInterface $request) : Token;
}
