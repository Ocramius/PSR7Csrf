<?php

declare(strict_types=1);

namespace PSR7Csrf;

use BadMethodCallException;
use InvalidArgumentException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\ValidationData;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PSR7Csrf\Exception\SessionAttributeNotFoundException;
use PSR7Csrf\HttpMethod\IsSafeHttpRequestInterface;
use PSR7Csrf\RequestParameter\ExtractCSRFParameterInterface;
use PSR7Csrf\Session\ExtractUniqueKeyFromSessionInterface;
use PSR7Sessions\Storageless\Session\SessionInterface;

final class CSRFCheckerMiddleware implements \Psr\Http\Server\MiddlewareInterface
{
    /**
     * @var Signer
     */
    private $signer;

    /**
     * @var Parser
     */
    private $tokenParser;

    /**
     * @var IsSafeHttpRequestInterface
     */
    private $isSafeHttpRequest;

    /**
     * @var ExtractUniqueKeyFromSessionInterface
     */
    private $extractUniqueKeyFromSession;

    /**
     * @var ExtractCSRFParameterInterface
     */
    private $extractCSRFParameter;

    /**
     * @var string
     */
    private $sessionAttribute;

    /**
     * @var ResponseInterface
     */
    private $faultyResponse;

    public function __construct(
        IsSafeHttpRequestInterface $isSafeHttpRequest,
        ExtractUniqueKeyFromSessionInterface $extractUniqueKeyFromSession,
        ExtractCSRFParameterInterface $extractCSRFParameter,
        Parser $tokenParser,
        Signer $signer,
        string $sessionAttribute,
        ResponseInterface $faultyResponse
    ) {
        $this->isSafeHttpRequest           = $isSafeHttpRequest;
        $this->extractUniqueKeyFromSession = $extractUniqueKeyFromSession;
        $this->extractCSRFParameter        = $extractCSRFParameter;
        $this->tokenParser                 = $tokenParser;
        $this->signer                      = $signer;
        $this->sessionAttribute            = $sessionAttribute;
        $this->faultyResponse              = $faultyResponse;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ) : ResponseInterface {
        if ($this->isSafeHttpRequest->__invoke($request)) {
            return $handler->handle($request);
        }

        try {
            $token = $this->tokenParser->parse($this->extractCSRFParameter->__invoke($request));

            if ($token->validate(new ValidationData())
                && $token->verify(
                    $this->signer,
                    $this->extractUniqueKeyFromSession->__invoke($this->getSession($request))
                )
            ) {
                return $handler->handle($request);
            }
        } catch (BadMethodCallException $invalidToken) {
            return $this->faultyResponse;
        } catch (InvalidArgumentException $invalidToken) {
            return $this->faultyResponse;
        }

        return $this->faultyResponse;
    }

    private function getSession(ServerRequestInterface $request) : SessionInterface
    {
        $session = $request->getAttribute($this->sessionAttribute);

        if (! $session instanceof SessionInterface) {
            throw SessionAttributeNotFoundException::fromAttributeNameAndRequest($this->sessionAttribute, $request);
        }

        return $session;
    }
}
