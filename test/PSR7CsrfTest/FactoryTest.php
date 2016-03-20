<?php

declare(strict_types=1);

namespace PSR7CsrfTest;

use PHPUnit_Framework_TestCase;
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
        self::assertInstanceOf(CSRFCheckerMiddleware::class, Factory::createDefaultCSRFCheckerMiddleware());
    }

    public function testCreateDefaultTokenGenerator()
    {
        self::assertInstanceOf(TokenGeneratorInterface::class, Factory::createDefaultTokenGenerator());
    }
}
