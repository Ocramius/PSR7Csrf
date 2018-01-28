<?php

declare(strict_types=1);

namespace PSR7Csrf\Session;

use PSR7Sessions\Storageless\Session\SessionInterface;

final class ExtractUniqueKeyFromSession implements ExtractUniqueKeyFromSessionInterface
{
    const ENTROPY = 32;

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
            $generatedKey = bin2hex(random_bytes(self::ENTROPY));

            $session->set($this->uniqueIdKey, $generatedKey);

            return $generatedKey;
        }

        return $uniqueKey;
    }
}
