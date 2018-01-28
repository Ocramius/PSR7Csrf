<?php

declare(strict_types=1);

namespace PSR7Csrf;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Csrf\Exception\InvalidExpirationTimeException;
use PSR7Csrf\Exception\SessionAttributeNotFoundException;
use PSR7Csrf\Session\ExtractUniqueKeyFromSessionInterface;
use PSR7Sessions\Storageless\Session\SessionInterface;

final class TokenGenerator implements TokenGeneratorInterface
{
    /**
     * @var Signer
     */
    private $signer;

    /**
     * @var ExtractUniqueKeyFromSessionInterface
     */
    private $extractUniqueKeyFromSession;

    /**
     * @var int
     */
    private $expirationTime;

    /**
     * @var string
     */
    private $sessionAttribute;

    /**
     * @param Signer                               $signer
     * @param ExtractUniqueKeyFromSessionInterface $extractUniqueKeyFromSession
     * @param int                                  $expirationTime
     * @param string                               $sessionAttribute
     *
     * @throws InvalidExpirationTimeException
     */
    public function __construct(
        Signer $signer,
        ExtractUniqueKeyFromSessionInterface $extractUniqueKeyFromSession,
        int $expirationTime,
        string $sessionAttribute
    ) {
        if ($expirationTime <= 0) {
            throw InvalidExpirationTimeException::fromInvalidExpirationTime($expirationTime);
        }

        $this->signer                      = $signer;
        $this->extractUniqueKeyFromSession = $extractUniqueKeyFromSession;
        $this->expirationTime              = $expirationTime;
        $this->sessionAttribute            = $sessionAttribute;
    }

    public function __invoke(ServerRequestInterface $request) : Token
    {
        $session = $request->getAttribute($this->sessionAttribute);

        if (! $session instanceof SessionInterface) {
            throw SessionAttributeNotFoundException::fromAttributeNameAndRequest($this->sessionAttribute, $request);
        }

        $timestamp = (new \DateTime())->getTimestamp();

        return (new Builder())
            ->setIssuedAt($timestamp)
            ->setExpiration($timestamp + $this->expirationTime)
            ->sign($this->signer, $this->extractUniqueKeyFromSession->__invoke($session))
            ->getToken();
    }
}
