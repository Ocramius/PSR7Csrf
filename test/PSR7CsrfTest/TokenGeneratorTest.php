<?php

declare(strict_types=1);

namespace PSR7CsrfTest;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Csrf\Exception\InvalidExpirationTimeException;
use PSR7Csrf\Session\ExtractUniqueKeyFromSessionInterface;
use PSR7Csrf\TokenGenerator;
use PSR7Session\Session\SessionInterface;

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

        new TokenGenerator($signer, $extractUniqueKeyFromSession, $invalidExpirationTime, 'session');
    }

    public function invalidExpirationTimeProvider() : array
    {
        return [
            [0],
            [-1],
            [-200],
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
        $extractUniqueKeyFromSession = $this->getMock(ExtractUniqueKeyFromSessionInterface::class);
        /* @var $session SessionInterface */
        $session = $this->getMock(SessionInterface::class);
        /* @var $request ServerRequestInterface|\PHPUnit_Framework_MockObject_MockObject */
        $request = $this->getMock(ServerRequestInterface::class);
        $sessionAttribute = uniqid('session', true);

        $generator = new TokenGenerator($signer, $extractUniqueKeyFromSession, $validExpirationTime, $sessionAttribute);
        $secretKey = uniqid('secretKey', true);

        $request->expects(self::any())->method('getAttribute')->with($sessionAttribute)->willReturn($session);
        $extractUniqueKeyFromSession->expects(self::any())->method('__invoke')->with($session)->willReturn($secretKey);

        $tokenString = $generator->__invoke($request);

        $token = (new Parser())->parse($tokenString);

        self::assertTrue($token->verify($signer, $secretKey));
    }

    public function validExpirationTimeProvider() : array
    {
        return [
            [10],
            [100],
        ];
    }
}
