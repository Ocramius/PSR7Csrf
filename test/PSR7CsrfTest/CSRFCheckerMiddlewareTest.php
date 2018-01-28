<?php

declare(strict_types=1);

namespace PSR7CsrfTest;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PSR7Csrf\CSRFCheckerMiddleware;
use PSR7Csrf\Exception\SessionAttributeNotFoundException;
use PSR7Csrf\HttpMethod\IsSafeHttpRequestInterface;
use PSR7Csrf\RequestParameter\ExtractCSRFParameterInterface;
use PSR7Csrf\Session\ExtractUniqueKeyFromSessionInterface;
use PSR7Sessions\Storageless\Session\SessionInterface;
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
     * @var RequestHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $nextMiddleware;

    /**
     * @var CSRFCheckerMiddleware
     */
    private $middleware;

    /**
     * @var ResponseInterface
     */
    private $faultyResponse;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->signer                      = new Signer\Hmac\Sha256();
        $this->tokenParser                 = new Parser();
        $this->isSafeHttpRequest           = $this->createMock(IsSafeHttpRequestInterface::class);
        $this->extractUniqueKeyFromSession = $this->createMock(ExtractUniqueKeyFromSessionInterface::class);
        $this->extractCSRFParameter        = $this->createMock(ExtractCSRFParameterInterface::class);
        $this->request                     = $this->createMock(ServerRequestInterface::class);
        $this->response                    = $this->createMock(ResponseInterface::class);
        $this->session                     = $this->createMock(SessionInterface::class);
        $this->sessionAttribute            = uniqid('session', true);
        $this->nextMiddleware              = $this->createMock(RequestHandlerInterface::class);
        $this->faultyResponse              = $this->createMock(ResponseInterface::class);
        $this->middleware                  = new CSRFCheckerMiddleware(
            $this->isSafeHttpRequest,
            $this->extractUniqueKeyFromSession,
            $this->extractCSRFParameter,
            $this->tokenParser,
            $this->signer,
            $this->sessionAttribute,
            $this->faultyResponse
        );
    }

    public function testWillIgnoreSafeRequestsWithNoNextMiddleware()
    {
        $this->isSafeHttpRequest->expects(self::any())->method('__invoke')->with($this->request)->willReturn(true);

        $this
            ->nextMiddleware
            ->expects(self::once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        self::assertSame($this->response, $this->middleware->process($this->request, $this->nextMiddleware));
    }

    public function testWillSucceedIfANonSafeRequestIsProvidedWithAValidTokenWithNextMiddleware()
    {
        $secret          = uniqid('secret', true);
        $validToken      = (new Builder())
            ->sign($this->signer, $secret)
            ->getToken();

        $this
            ->nextMiddleware
            ->expects(self::once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);
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
            $this->middleware->process($this->request, $this->nextMiddleware)
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

        $this->assertFaultyResponse();
    }

    public function testUnsignedTokensAreRejected()
    {
        $secret     = uniqid('secret', true);
        $validToken = (new Builder())->getToken();

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

        $this->assertFaultyResponse();
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

        $this->assertFaultyResponse();
    }

    public function testMalformedTokensShouldBeRejected()
    {
        $this->isSafeHttpRequest->expects(self::any())->method('__invoke')->with($this->request)->willReturn(false);
        $this
            ->extractUniqueKeyFromSession
            ->expects(self::any())
            ->method('__invoke')
            ->with($this->session)
            ->willReturn(uniqid('secret', true));
        $this
            ->extractCSRFParameter
            ->expects(self::any())
            ->method('__invoke')
            ->with($this->request)
            ->willReturn('yadda yadda invalid bs');
        $this
            ->request
            ->expects(self::any())
            ->method('getAttribute')
            ->with($this->sessionAttribute)
            ->willReturn($this->session);

        $this->assertFaultyResponse();
    }

    public function testWillFailIfARequestDoesNotIncludeASession()
    {
        $this->isSafeHttpRequest->expects(self::any())->method('__invoke')->with($this->request)->willReturn(false);
        $this
            ->extractCSRFParameter
            ->expects(self::any())
            ->method('__invoke')
            ->with($this->request)
            ->willReturn((new Builder())->getToken());
        $this
            ->request
            ->expects(self::any())
            ->method('getAttribute')
            ->with($this->sessionAttribute)
            ->willReturn(new stdClass());
        $this
            ->request
            ->expects(self::any())
            ->method('getAttributes')
            ->willReturn([]);

        $this->expectException(SessionAttributeNotFoundException::class);

        $this->middleware->process($this->request, $this->nextMiddleware);
    }

    private function assertFaultyResponse() : void
    {
        $this->nextMiddleware->expects(self::never())->method('handle');

        self::assertSame($this->faultyResponse, $this->middleware->process($this->request, $this->nextMiddleware));
    }
}
