<?php

declare(strict_types=1);

namespace PSR7Csrf\Session;

use PSR7Sessions\Storageless\Session\SessionInterface;

interface ExtractUniqueKeyFromSessionInterface
{
    public function __invoke(SessionInterface $session) : string;
}
