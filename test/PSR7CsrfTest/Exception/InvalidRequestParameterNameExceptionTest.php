<?php

declare(strict_types=1);

namespace PSR7CsrfTest\Exception;

use InvalidArgumentException;
use Lcobucci\JWT\Signer;
use PHPUnit_Framework_TestCase;
use PSR7Csrf\Exception\ExceptionInterface;
use PSR7Csrf\Exception\InvalidRequestParameterNameException;

/**
 * @covers \PSR7Csrf\Exception\InvalidRequestParameterNameException
 */
final class InvalidRequestParameterNameExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testFromEmptyRequestParameterName()
    {
        $exception = InvalidRequestParameterNameException::fromEmptyRequestParameterName();

        self::assertInstanceOf(InvalidRequestParameterNameException::class, $exception);
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertSame('The given request parameter must be a non-empty string', $exception->getMessage());
    }
}
