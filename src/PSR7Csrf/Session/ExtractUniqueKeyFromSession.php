<?php

declare(strict_types=1);

namespace PSR7Csrf\Session;

use Lcobucci\JWT\Signer;
use PSR7Session\Session\SessionInterface;

final class ExtractUniqueKeyFromSession implements ExtractUniqueKeyFromSessionInterface
{
    /**
     * @var string
     */
    private $uniqueIdKey;

    public function __construct(string $uniqueIdKey)
    {
        $this->uniqueIdKey = $uniqueIdKey;
    }

    public function __invoke(SessionInterface $session) : string
    {
        $uniqueKey = $session->get($this->uniqueIdKey, '');

        if ('' === $uniqueKey || ! is_string($uniqueKey)) {
            $generatedKey = bin2hex(random_bytes(32));

            $session->set($this->uniqueIdKey, $generatedKey);

            return $generatedKey;
        }

        return $uniqueKey;
    }
}
