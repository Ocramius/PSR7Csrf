<?php

declare(strict_types=1);

namespace PSR7CsrfTest;

use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Csrf\Exception\InvalidExpirationTimeException;
use PSR7Csrf\Exception\SessionAttributeNotFoundException;
use PSR7Csrf\Session\ExtractUniqueKeyFromSessionInterface;
use PSR7Csrf\TokenGenerator;
use PSR7Sessions\Storageless\Session\SessionInterface;
use stdClass;

/**
 * @covers \PSR7Csrf\TokenGenerator
 */
final class TokenGeneratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider invalidExpirationTimeProvider
     *
     * @param int  $invalidExpirationTime
     * @param bool $valid
     */
    public function testWillRejectInvalidExpirationTime(int $invalidExpirationTime, bool $valid)
    {
        /* @var $signer Signer */
        $signer                      = $this->createMock(Signer::class);
        /* @var $extractUniqueKeyFromSession ExtractUniqueKeyFromSessionInterface */
        $extractUniqueKeyFromSession = $this->createMock(ExtractUniqueKeyFromSessionInterface::class);

        if (! $valid) {
            $this->expectException(InvalidExpirationTimeException::class);
        }

        self::assertInstanceOf(
            TokenGenerator::class,
            new TokenGenerator($signer, $extractUniqueKeyFromSession, $invalidExpirationTime, 'session')
        );
    }

    public function invalidExpirationTimeProvider() : array
    {
        return [
            [100, true],
            [1, true],
            [0, false],
            [-1, false],
            [-200, false],
        ];
    }

    /**
     * @dataProvider validExpirationTimeProvider
     *
     * @param int $validExpirationTime
     */
    public function testWillGenerateAValidJWTToken(int $validExpirationTime)
    {
        $signer = new Sha256();
        /* @var $extractUniqueKeyFromSession ExtractUniqueKeyFromSessionInterface|\PHPUnit_Framework_MockObject_MockObject */
        $extractUniqueKeyFromSession = $this->createMock(ExtractUniqueKeyFromSessionInterface::class);
        /* @var $session SessionInterface */
        $session = $this->createMock(SessionInterface::class);
        /* @var $request ServerRequestInterface|\PHPUnit_Framework_MockObject_MockObject */
        $request = $this->createMock(ServerRequestInterface::class);
        $sessionAttribute = uniqid('session', true);

        $generator = new TokenGenerator($signer, $extractUniqueKeyFromSession, $validExpirationTime, $sessionAttribute);
        $secretKey = uniqid('secretKey', true);

        $request->expects(self::any())->method('getAttribute')->with($sessionAttribute)->willReturn($session);
        $extractUniqueKeyFromSession->expects(self::any())->method('__invoke')->with($session)->willReturn($secretKey);

        $token = $generator->__invoke($request);

        self::assertTrue($token->verify($signer, $secretKey));
        self::assertLessThanOrEqual(time(), $token->getClaim('iat'));
        self::assertGreaterThan(time(), $token->getClaim('exp'));
    }

    public function validExpirationTimeProvider() : array
    {
        return [
            [10],
            [100],
        ];
    }

    public function testWillFailIfTheSessionAttributeIsNotASession()
    {
        /* @var $extractUniqueKeyFromSession ExtractUniqueKeyFromSessionInterface|\PHPUnit_Framework_MockObject_MockObject */
        $extractUniqueKeyFromSession = $this->createMock(ExtractUniqueKeyFromSessionInterface::class);
        /* @var $request ServerRequestInterface|\PHPUnit_Framework_MockObject_MockObject */
        $request = $this->createMock(ServerRequestInterface::class);
        $sessionAttribute = uniqid('session', true);

        $generator = new TokenGenerator(new Sha256(), $extractUniqueKeyFromSession, 10, $sessionAttribute);

        $request->expects(self::any())->method('getAttribute')->with($sessionAttribute)->willReturn(new stdClass());
        $request->expects(self::any())->method('getAttributes')->willReturn([]);

        $this->expectException(SessionAttributeNotFoundException::class);

        $generator->__invoke($request);
    }
}
