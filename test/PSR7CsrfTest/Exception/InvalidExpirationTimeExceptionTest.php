<?php

declare(strict_types=1);

namespace PSR7CsrfTest\Exception;

use InvalidArgumentException;
use Lcobucci\JWT\Signer;
use PHPUnit_Framework_TestCase;
use PSR7Csrf\Exception\ExceptionInterface;
use PSR7Csrf\Exception\InvalidExpirationTimeException;

/**
 * @covers \PSR7Csrf\Exception\InvalidExpirationTimeException
 */
final class InvalidExpirationTimeExceptionTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $exception = InvalidExpirationTimeException::fromInvalidExpirationTime(-4);

        self::assertInstanceOf(InvalidExpirationTimeException::class, $exception);
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        self::assertInstanceOf(ExceptionInterface::class, $exception);
        self::assertSame('The provided expiration time -4 is invalid: expected a >0 integer', $exception->getMessage());
    }
}
