<?php

declare(strict_types=1);

namespace PSR7CsrfTest;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use PSR7Csrf\CSRFCheckerMiddleware;
use PSR7Csrf\HttpMethod\IsSafeHttpRequestInterface;
use PSR7Csrf\RequestParameter\ExtractCSRFParameterInterface;
use PSR7Csrf\Session\ExtractUniqueKeyFromSessionInterface;
use PSR7Session\Session\SessionInterface;
use stdClass;

/**
 * @covers \PSR7Csrf\CSRFCheckerMiddleware
 */
final class CSRFCheckerMiddlewareTest extends PHPUnit_Framework_TestCase
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
     * @var IsSafeHttpRequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $isSafeHttpRequest;

    /**
     * @var ExtractUniqueKeyFromSessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $extractUniqueKeyFromSession;

    /**
     * @var ExtractCSRFParameterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $extractCSRFParameter;

    /**
     * @var ServerRequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    /**
     * @var string
     */
    private $sessionAttribute;

    /**
     * @var callable|\PHPUnit_Framework_MockObject_MockObject
     */
    private $nextMiddleware;

    /**
     * @var CSRFCheckerMiddleware
     */
    private $middleware;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->signer                      = new Signer\Hmac\Sha256();
        $this->tokenParser                 = new Parser();
        $this->isSafeHttpRequest           = $this->getMock(IsSafeHttpRequestInterface::class);
        $this->extractUniqueKeyFromSession = $this->getMock(ExtractUniqueKeyFromSessionInterface::class);
        $this->extractCSRFParameter        = $this->getMock(ExtractCSRFParameterInterface::class);
        $this->request                     = $this->getMock(ServerRequestInterface::class);
        $this->response                    = $this->getMock(ResponseInterface::class);
        $this->session                     = $this->getMock(SessionInterface::class);
        $this->sessionAttribute            = uniqid('session', true);
        $this->nextMiddleware              = $this->getMock(stdClass::class, ['__invoke']);
        $this->middleware                  = new CSRFCheckerMiddleware(
            $this->isSafeHttpRequest,
            $this->extractUniqueKeyFromSession,
            $this->extractCSRFParameter,
            $this->tokenParser,
            $this->signer,
            $this->sessionAttribute
        );
    }

    public function testWillIgnoreSafeRequestsWithNoNextMiddleware()
    {
        $this->isSafeHttpRequest->expects(self::any())->method('__invoke')->with($this->request)->willReturn(true);

        self::assertSame($this->response, $this->middleware->__invoke($this->request, $this->response));
    }

    public function testWillIgnoreSafeRequestsWithoutNextMiddleware()
    {
        $nextReturnValue = $this->getMock(ResponseInterface::class);

        $this->nextMiddleware->expects(self::once())->method('__invoke')->willReturn($nextReturnValue);
        $this->isSafeHttpRequest->expects(self::any())->method('__invoke')->with($this->request)->willReturn(true);

        self::assertSame(
            $nextReturnValue,
            $this->middleware->__invoke($this->request, $this->response, $this->nextMiddleware)
        );
    }

    public function testWillSucceedIfANonSafeRequestIsProvidedWithAValidTokenWithoutNextMiddleware()
    {
        $secret     = uniqid('secret', true);
        $validToken = (new Builder())
            ->sign($this->signer, $secret)
            ->getToken();

        $this->isSafeHttpRequest->expects(self::any())->method('__invoke')->with($this->request)->willReturn(false);
        $this
            ->extractUniqueKeyFromSession
            ->expects(self::any())
            ->method('__invoke')
            ->with($this->session)
            ->willReturn($secret);
        $this
            ->extractCSRFParameter
            ->expects(self::any())
            ->method('__invoke')
            ->with($this->request)
            ->willReturn((string) $validToken);
        $this
            ->request
            ->expects(self::any())
            ->method('getAttribute')
            ->with($this->sessionAttribute)
            ->willReturn($this->session);

        self::assertSame(
            $this->response,
            $this->middleware->__invoke($this->request, $this->response)
        );
    }

    public function testWillSucceedIfANonSafeRequestIsProvidedWithAValidTokenWithNextMiddleware()
    {
        $nextReturnValue = $this->getMock(ResponseInterface::class);
        $secret          = uniqid('secret', true);
        $validToken      = (new Builder())
            ->sign($this->signer, $secret)
            ->getToken();

        $this->nextMiddleware->expects(self::once())->method('__invoke')->willReturn($nextReturnValue);
        $this->isSafeHttpRequest->expects(self::any())->method('__invoke')->with($this->request)->willReturn(false);
        $this
            ->extractUniqueKeyFromSession
            ->expects(self::any())
            ->method('__invoke')
            ->with($this->session)
            ->willReturn($secret);
        $this
            ->extractCSRFParameter
            ->expects(self::any())
            ->method('__invoke')
            ->with($this->request)
            ->willReturn((string) $validToken);
        $this
            ->request
            ->expects(self::any())
            ->method('getAttribute')
            ->with($this->sessionAttribute)
            ->willReturn($this->session);

        self::assertSame(
            $nextReturnValue,
            $this->middleware->__invoke($this->request, $this->response, $this->nextMiddleware)
        );
    }

    public function testNonMatchingSignedTokensAreRejected()
    {
        $secret          = uniqid('secret', true);
        $validToken      = (new Builder())
            ->sign($this->signer, uniqid('wrongSecret', true))
            ->getToken();

        $this->isSafeHttpRequest->expects(self::any())->method('__invoke')->with($this->request)->willReturn(false);
        $this
            ->extractUniqueKeyFromSession
            ->expects(self::any())
            ->method('__invoke')
            ->with($this->session)
            ->willReturn($secret);
        $this
            ->extractCSRFParameter
            ->expects(self::any())
            ->method('__invoke')
            ->with($this->request)
            ->willReturn((string) $validToken);
        $this
            ->request
            ->expects(self::any())
            ->method('getAttribute')
            ->with($this->sessionAttribute)
            ->willReturn($this->session);

        $this->assertFaultyResponse($this->middleware, $this->request, $this->response);
    }


    public function testExpiredSignedTokensAreRejected()
    {
        $secret          = uniqid('secret', true);
        $validToken      = (new Builder())
            ->setExpiration(time() - 3600)
            ->sign($this->signer, $secret)
            ->getToken();

        $this->isSafeHttpRequest->expects(self::any())->method('__invoke')->with($this->request)->willReturn(false);
        $this
            ->extractUniqueKeyFromSession
            ->expects(self::any())
            ->method('__invoke')
            ->with($this->session)
            ->willReturn($secret);
        $this
            ->extractCSRFParameter
            ->expects(self::any())
            ->method('__invoke')
            ->with($this->request)
            ->willReturn((string) $validToken);
        $this
            ->request
            ->expects(self::any())
            ->method('getAttribute')
            ->with($this->sessionAttribute)
            ->willReturn($this->session);

        $this->assertFaultyResponse($this->middleware, $this->request, $this->response);
    }

    /**
     * @param CSRFCheckerMiddleware                                      $middleware
     * @param ServerRequestInterface                                     $request
     * @param ResponseInterface|\PHPUnit_Framework_MockObject_MockObject $response
     *
     * @return void
     */
    private function assertFaultyResponse(
        CSRFCheckerMiddleware $middleware,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        $faultyResponse = $this->getMock(ResponseInterface::class);
        $responseBody   = $this->getMock(StreamInterface::class);

        $response->expects(self::any())->method('withStatus')->with(400)->willReturn($faultyResponse);
        $faultyResponse->expects(self::any())->method('getBody')->willReturn($responseBody);
        $responseBody->expects(self::once())->method('write')->with('{"error":"missing or invalid CSRF token"}');

        self::assertSame($faultyResponse, $middleware->__invoke($request, $response));
    }
}
