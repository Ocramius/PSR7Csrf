<?php

declare(strict_types=1);

namespace PSR7CsrfTest;

use Lcobucci\JWT\Signer;
use PHPUnit_Framework_TestCase;
use PSR7Csrf\Exception\InvalidExpirationTimeException;
use PSR7Csrf\Session\ExtractUniqueKeyFromSessionInterface;
use PSR7Csrf\TokenGenerator;

/**
 * @covers \PSR7Csrf\TokenGenerator
 */
final class TokenGeneratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider invalidExpirationTimeProvider
     *
     * @param int $invalidExpirationTime
     */
    public function testWillRejectInvalidExpirationTime(int $invalidExpirationTime)
    {
        /* @var $signer Signer */
        $signer                      = $this->getMock(Signer::class);
        /* @var $extractUniqueKeyFromSession ExtractUniqueKeyFromSessionInterface */
        $extractUniqueKeyFromSession = $this->getMock(ExtractUniqueKeyFromSessionInterface::class);

        $this->expectException(InvalidExpirationTimeException::class);

        new TokenGenerator($signer, $extractUniqueKeyFromSession, $invalidExpirationTime);
    }

    public function invalidExpirationTimeProvider() : array
    {
        return [
            [0],
            [-1],
            [-200],
        ];
    }
}
