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
        $session     = $this->createMock(SessionInterface::class);
        $superSecret = uniqid('', true);

        $session->expects(self::any())->method('get')->with($key, '')->willReturn($superSecret);
        $session->expects(self::never())->method('set');

        self::assertSame($superSecret, (new ExtractUniqueKeyFromSession($key))->__invoke($session));
    }

    /**
     * @dataProvider keysProvider
     *
     * @param string $key
     */
    public function testExtractionWithEmptyExistingKey(string $key)
    {
        $extractKey = new ExtractUniqueKeyFromSession($key);

        /* @var $session SessionInterface|\PHPUnit_Framework_MockObject_MockObject */
        $session = $this->createMock(SessionInterface::class);

        $session->expects(self::any())->method('get')->with($key, '')->willReturn('');
        $session->expects(self::exactly(2))->method('set')->with(
            $key,
            self::callback(function (string $secret) {
                self::assertNotEmpty($secret);

                return true;
            })
        );

        $secretUniqueKey = $extractKey->__invoke($session);

        self::assertInternalType('string', $secretUniqueKey);
        self::assertNotEmpty($secretUniqueKey);

        $anotherSecretKey = $extractKey->__invoke($session);

        self::assertInternalType('string', $anotherSecretKey);
        self::assertNotEmpty($anotherSecretKey);
        self::assertNotEquals($secretUniqueKey, $anotherSecretKey);
    }

    /**
     * @dataProvider keysProvider
     *
     * @param string $key
     */
    public function testExtractionWithNonStringExistingKey(string $key)
    {
        $extractKey = new ExtractUniqueKeyFromSession($key);

        /* @var $session SessionInterface|\PHPUnit_Framework_MockObject_MockObject */
        $session = $this->createMock(SessionInterface::class);

        $session->expects(self::any())->method('get')->with($key, '')->willReturn(123);
        $session->expects(self::exactly(2))->method('set')->with(
            $key,
            self::callback(function (string $secret) {
                self::assertNotEmpty($secret);

                return true;
            })
        );

        $secretUniqueKey = $extractKey->__invoke($session);

        self::assertInternalType('string', $secretUniqueKey);
        self::assertNotEmpty($secretUniqueKey);

        $anotherSecretKey = $extractKey->__invoke($session);

        self::assertInternalType('string', $anotherSecretKey);
        self::assertNotEmpty($anotherSecretKey);
        self::assertNotEquals($secretUniqueKey, $anotherSecretKey);
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
