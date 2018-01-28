<?php

declare(strict_types=1);

namespace PSR7CsrfTest;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PSR7Csrf\CSRFCheckerMiddleware;
use PSR7Csrf\Factory;
use PSR7Csrf\TokenGeneratorInterface;

/**
 * @covers \PSR7Csrf\Factory
 */
final class FactoryTest extends PHPUnit_Framework_TestCase
{
    public function testCreateDefaultCSRFCheckerMiddleware()
    {
        $faultyResponse = $this->createMock(ResponseInterface::class);

        $middleware = Factory::createDefaultCSRFCheckerMiddleware($faultyResponse);

        self::assertInstanceOf(CSRFCheckerMiddleware::class, $middleware);

        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects(self::any())
            ->method('getMethod')
            ->willReturn('POST');

        self::assertSame(
            $faultyResponse,
            $middleware->process(
                $request,
                $this->createMock(RequestHandlerInterface::class)
            ),
            'Faulty http response passed to the factory is returned as part of a failed CSRF validation'
        );
    }

    public function testCreateDefaultTokenGenerator()
    {
        self::assertInstanceOf(TokenGeneratorInterface::class, Factory::createDefaultTokenGenerator());
    }
}
