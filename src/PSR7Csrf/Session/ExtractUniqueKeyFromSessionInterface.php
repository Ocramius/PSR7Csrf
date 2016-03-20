<?php

declare(strict_types=1);

namespace PSR7Csrf\Session;

use Lcobucci\JWT\Signer;
use PSR7Session\Session\SessionInterface;

interface ExtractUniqueKeyFromSessionInterface
{
    public function __invoke(SessionInterface $session) : string;
}
