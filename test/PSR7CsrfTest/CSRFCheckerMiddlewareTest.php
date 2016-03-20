<?php

declare(strict_types=1);

namespace PSR7CsrfTest;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Csrf\CSRFCheckerMiddleware;
use PSR7Csrf\HttpMethod\IsSafeHttpRequestInterface;
use PSR7Csrf\RequestParameter\ExtractCSRFParameterInterface;
use PSR7Csrf\Session\ExtractUniqueKeyFromSessionInterface;
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
        $this->nextMiddleware              = $this->getMock(stdClass::class, ['__invoke']);
        $this->middleware                  = new CSRFCheckerMiddleware(
            $this->isSafeHttpRequest,
            $this->extractUniqueKeyFromSession,
            $this->extractCSRFParameter,
            $this->tokenParser,
            $this->signer
        );
    }

    public function testWillIgnoreSafeRequestsWithNoOutMiddleware()
    {
        $this->isSafeHttpRequest->expects(self::any())->method('__invoke')->with($this->request)->willReturn(true);

        self::assertSame($this->response, $this->middleware->__invoke($this->request, $this->response));
    }

    public function testWillIgnoreSafeRequestsWithOutMiddleware()
    {
        $nextReturnValue = new stdClass();

        $this->nextMiddleware->expects(self::once())->method('__invoke')->willReturn($nextReturnValue);
        $this->isSafeHttpRequest->expects(self::any())->method('__invoke')->with($this->request)->willReturn(true);

        self::assertSame(
            $nextReturnValue,
            $this->middleware->__invoke($this->request, $this->response, $this->nextMiddleware)
        );
    }
}
