<?php

declare(strict_types=1);

namespace PSR7CsrfTest\HttpMethod;

use Lcobucci\JWT\Signer;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use PSR7Csrf\HttpMethod\IsSafeHttpRequest;

final class IsSafeHttpRequestTest extends PHPUnit_Framework_TestCase
{
    public function testSafeMethods(array $safeMethods, string $httpMethod, bool $expectedResult)
    {
        /* @var $request RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
        $request = $this->getMock(RequestInterface::class);

        $request->expects(self::any())->method('getMethod')->willReturn($httpMethod);

        self::assertSame($expectedResult, (new IsSafeHttpRequest(...$safeMethods))->__invoke($request));
    }

    public function httpMethodsProvider() : array
    {
        return [
            'empty' => [
                [],
                'GET',
                false,
            ],
            'GET only' => [
                ['GET'],
                'GET',
                true,
            ],
            'GET only, non-matching method' => [
                ['GET'],
                'PUT',
                false,
            ],
        ];
    }
}
