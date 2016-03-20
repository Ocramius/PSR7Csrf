<?php

declare(strict_types=1);

namespace PSR7CsrfTest\RequestParameter;

use Lcobucci\JWT\Signer;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Csrf\Exception\InvalidRequestParameterNameException;
use PSR7Csrf\RequestParameter\ExtractCSRFParameter;

/**
 * @covers \PSR7Csrf\RequestParameter\ExtractCSRFParameter
 */
final class ExtractCSRFParameterTest extends PHPUnit_Framework_TestCase
{
    public function testRejectsEmptyRequestParameterName()
    {
        $this->expectException(InvalidRequestParameterNameException::class);

        new ExtractCSRFParameter('');
    }

    /**
     * @dataProvider requestBodyProvider
     *
     * @param string            $requestParameter
     * @param null|object|array $body
     * @param string            $expectedExtractedValue
     *
     * @return void
     */
    public function testExtraction(string $requestParameter, $body, string $expectedExtractedValue)
    {
        /* @var $request ServerRequestInterface|\PHPUnit_Framework_MockObject_MockObject */
        $request = $this->getMock(ServerRequestInterface::class);

        $request->expects(self::any())->method('getParsedBody')->willReturn($body);

        self::assertSame($expectedExtractedValue, (new ExtractCSRFParameter($requestParameter))->__invoke($request));
    }

    public function requestBodyProvider()
    {
        return [
            'null body' => [
                'request parameter name',
                null,
                '',
            ],
            'empty array' => [
                'request parameter name',
                [],
                '',
            ],
        ];
    }
}
