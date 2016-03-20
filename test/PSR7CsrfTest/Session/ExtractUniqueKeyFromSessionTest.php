<?php

declare(strict_types=1);

namespace PSR7CsrfTest\HttpMethod;

use Lcobucci\JWT\Signer;
use PHPUnit_Framework_TestCase;
use PSR7Csrf\Session\ExtractUniqueKeyFromSession;
use PSR7Session\Session\SessionInterface;

/**
 * @covers \PSR7Csrf\Session\ExtractUniqueKeyFromSession
 */
final class ExtractUniqueKeyFromSessionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider keysProvider
     *
     * @param string $key
     */
    public function testExtractionWithExistingKey(string $key)
    {
        /* @var $session SessionInterface|\PHPUnit_Framework_MockObject_MockObject */
        $session     = $this->getMock(SessionInterface::class);
        $superSecret = uniqid('', true);

        $session->expects(self::any())->method('get')->with($key, '')->willReturn($superSecret);
        $session->expects(self::never())->method('set');

        self::assertSame($superSecret, (new ExtractUniqueKeyFromSession($key))->__invoke($session));
    }

    public function keysProvider() : array
    {
        return [
            [''],
            ['key'],
            ['123'],
            ['123 456'],
        ];
    }
}
