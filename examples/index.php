<?php

declare(strict_types=1);

namespace PSR7Csrf\Example;

use Dflydev\FigCookies\SetCookie;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PSR7Csrf\Factory;
use PSR7Csrf\TokenGeneratorInterface;
use PSR7Sessions\Storageless\Http\SessionMiddleware;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\ServerRequestFactory;

require_once __DIR__ . '/vendor/autoload.php';

// We use PSR7Sessions\Storageless here - if you don't like it, you need to provide
// an equivalent session middleware implementation.
$sessionMiddleware = new SessionMiddleware(
    new Sha256(),
    'c9UA8QKLSmDEn4DhNeJIad/4JugZd/HvrjyKrS0jOes=', // signature key (important: change this to your own)
    'c9UA8QKLSmDEn4DhNeJIad/4JugZd/HvrjyKrS0jOes=', // verification key (important: change this to your own)
    SetCookie::create('an-example-cookie-name')
             ->withSecure(false)// false on purpose, unless you have https locally - DO NOT DO THIS IN PRODUCTION!
             ->withHttpOnly(true),
    new Parser(),
    1200,
    new SystemClock()
);

/**
 * This is the actual component from this package. Setup assumes that
 * a `SessionMiddleware` was previously piped through. If you don't do that,
 * then all requests will fail CSRF validation!
 */
$csrfMiddleware = Factory::createDefaultCSRFCheckerMiddleware(
    (new JsonResponse(['error' => 'CSRF validation failed']))->withStatus(401)
);

/**
 * The token generator is needed to generate CSRF tokens to be added to your forms
 */
$tokenGenerator = Factory::createDefaultTokenGenerator();

/**
 * This is an example of how you'd generate a form with a CSRF token from this package
 */
$action = new class ($tokenGenerator) implements RequestHandlerInterface
{
    /**
     * @var TokenGeneratorInterface
     */
    private $tokenGenerator;

    public function __construct(TokenGeneratorInterface $tokenGenerator)
    {
        $this->tokenGenerator = $tokenGenerator;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if ('GET' === \strtoupper($request->getMethod())) {
            return new HtmlResponse(
                '<form method="post" action="/post">'
                . '<input type="submit" value="Submit with CSRF token"/>'
                . '<input type="hidden" name="csrf_token" value="'
                . $this->tokenGenerator->__invoke($request)
                . '"/>'
                . '</form>'
                . '<form method="post" action="/post">'
                . '<input type="submit" value="Submit without CSRF token"/>'
                . '</form>'
            );
        }

        return new TextResponse('It works!');
    }
};

/**
 * Don't panic! This just emulates what a typical middleware-based HTTP framework does internally
 */
$pipe = new class ($action, $sessionMiddleware, $csrfMiddleware) implements RequestHandlerInterface
{
    /**
     * @var RequestHandlerInterface
     */
    private $action;

    /**
     * @var MiddlewareInterface[]
     */
    private $pipedMiddleware;

    public function __construct(RequestHandlerInterface $action, MiddlewareInterface ...$pipedMiddleware)
    {
        $this->action = $action;
        $this->pipedMiddleware = $pipedMiddleware;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if (! $this->pipedMiddleware) {
            return $this->action->handle($request);
        }

        $middleware = \reset($this->pipedMiddleware);

        return $this->pipedMiddleware[0]->process(
            $request,
            new self($this->action, ...\array_values(\array_slice($this->pipedMiddleware, 1)))
        );
    }
};

// produce the response
(new SapiEmitter())
    ->emit($pipe->handle(ServerRequestFactory::fromGlobals()));
