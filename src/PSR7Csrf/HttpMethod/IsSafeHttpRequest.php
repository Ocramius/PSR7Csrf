<?php

declare(strict_types=1);

namespace PSR7Csrf\HttpMethod;

use Lcobucci\JWT\Signer;
use Psr\Http\Message\RequestInterface;

final class IsSafeHttpRequest implements IsSafeHttpRequestInterface
{
    const STRICT_CHECKING = true;

    /**
     * @var \string[]
     */
    private $safeMethods;

    public function __construct(string ...$safeMethods)
    {
        $this->safeMethods = array_map('strtoupper', $safeMethods);
    }

    public static function fromDefaultSafeMethods() : self
    {
        return new self('GET', 'HEAD', 'OPTIONS');
    }

    public function __invoke(RequestInterface $request) : bool
    {
        return in_array(strtoupper($request->getMethod()), $this->safeMethods, self::STRICT_CHECKING);
    }
}
