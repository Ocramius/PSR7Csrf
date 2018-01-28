<?php

declare(strict_types=1);

namespace PSR7CsrfTest\RequestParameter;

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
        $request = $this->createMock(ServerRequestInterface::class);

        $request->expects(self::any())->method('getParsedBody')->willReturn($body);

        self::assertSame($expectedExtractedValue, (new ExtractCSRFParameter($requestParameter))->__invoke($request));
    }

    public function requestBodyProvider()
    {
        /** @noinspection PhpUnusedPrivateFieldInspection */
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
            'empty object' => [
                'request parameter name',
                (object) [],
                '',
            ],
            'array with matching parameter' => [
                'request parameter name',
                ['request parameter name' => 'foo'],
                'foo',
            ],
            'array with matching non-string parameter' => [
                'request parameter name',
                ['request parameter name' => 123],
                '',
            ],
            'object with matching parameter' => [
                'request parameter name',
                (object) ['request parameter name' => 'foo'],
                'foo',
            ],
            'object with matching non-string parameter' => [
                'request parameter name',
                (object) ['request parameter name' => 123],
                '',
            ],
            'class with private matching property' => [
                'field',
                new class {
                    private $field = 'bar';
                },
                '',
            ],
            'class with protected matching property' => [
                'field',
                new class {
                    protected $field = 'bar';
                },
                '',
            ],
            'class with public matching property' => [
                'field',
                new class {
                    public $field = 'bar';
                },
                'bar',
            ],
            'class with public matching non-string property' => [
                'field',
                new class {
                    public $field = 123;
                },
                '',
            ],
        ];
    }
}
