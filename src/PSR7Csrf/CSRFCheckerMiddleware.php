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
use PSR7Csrf\Exception\SessionAttributeNotFoundException;
use PSR7Csrf\HttpMethod\IsSafeHttpRequestInterface;
use PSR7Csrf\RequestParameter\ExtractCSRFParameterInterface;
use PSR7Csrf\Session\ExtractUniqueKeyFromSessionInterface;
use PSR7Session\Session\SessionInterface;
use Zend\Stratigility\MiddlewareInterface;

final class CSRFCheckerMiddleware implements MiddlewareInterface
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

    public function __construct(
        IsSafeHttpRequestInterface $isSafeHttpRequest,
        ExtractUniqueKeyFromSessionInterface $extractUniqueKeyFromSession,
        ExtractCSRFParameterInterface $extractCSRFParameter,
        Parser $tokenParser,
        Signer $signer,
        string $sessionAttribute
    ) {
        $this->isSafeHttpRequest           = $isSafeHttpRequest;
        $this->extractUniqueKeyFromSession = $extractUniqueKeyFromSession;
        $this->extractCSRFParameter        = $extractCSRFParameter;
        $this->tokenParser                 = $tokenParser;
        $this->signer                      = $signer;
        $this->sessionAttribute            = $sessionAttribute;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $out = null
    ) {
        if ($this->isSafeHttpRequest->__invoke($request)) {
            return $this->produceSuccessfulResponse($response, $out);
        }

        try {
            $token = $this->tokenParser->parse($this->extractCSRFParameter->__invoke($request));

            if ($token->validate(new ValidationData())
                && $token->verify(
                    $this->signer,
                    $this->extractUniqueKeyFromSession->__invoke($this->getSession($request))
                )
            ) {
                return $this->produceSuccessfulResponse($response, $out);
            }
        } catch (BadMethodCallException $invalidToken) {
            return $this->buildFaultyResponse($response);
        } catch (InvalidArgumentException $invalidToken) {
            return $this->buildFaultyResponse($response);
        }

        return $this->buildFaultyResponse($response);
    }

    private function getSession(ServerRequestInterface $request) : SessionInterface
    {
        $session = $request->getAttribute($this->sessionAttribute);

        if (! $session instanceof SessionInterface) {
            throw SessionAttributeNotFoundException::fromAttributeNameAndRequest($this->sessionAttribute, $request);
        }

        return $session;
    }

    private function produceSuccessfulResponse(ResponseInterface $response, callable $out = null)
    {
        return $out ? $out() : $response;
    }

    private function buildFaultyResponse(ResponseInterface $response) : ResponseInterface
    {
        $faultyResponse = $response->withStatus(400);

        $faultyResponse->getBody()->write('{"error":"missing or invalid CSRF token"}');

        return $faultyResponse;
    }
}
