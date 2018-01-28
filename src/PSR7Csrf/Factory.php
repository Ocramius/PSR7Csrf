<?php

declare(strict_types=1);

namespace PSR7Csrf;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Psr\Http\Message\ResponseInterface;
use PSR7Csrf\HttpMethod\IsSafeHttpRequest;
use PSR7Csrf\RequestParameter\ExtractCSRFParameter;
use PSR7Csrf\Session\ExtractUniqueKeyFromSession;
use PSR7Sessions\Storageless\Http\SessionMiddleware;

final class Factory
{
    public const DEFAULT_SIGNATURE_KEY_NAME = 'csrf_signature_key';

    public const DEFAULT_CSRF_DATA_KEY = 'csrf_token';

    public const DEFAULT_EXPIRATION_TIME = 60 * 24;

    public static function createDefaultCSRFCheckerMiddleware(
        ResponseInterface $failedCsrfValidationResponse
    ) : CSRFCheckerMiddleware {
        return new CSRFCheckerMiddleware(
            IsSafeHttpRequest::fromDefaultSafeMethods(),
            new ExtractUniqueKeyFromSession(self::DEFAULT_SIGNATURE_KEY_NAME),
            new ExtractCSRFParameter(self::DEFAULT_CSRF_DATA_KEY),
            new Parser(),
            new Sha256(),
            SessionMiddleware::SESSION_ATTRIBUTE,
            $failedCsrfValidationResponse
        );
    }

    public static function createDefaultTokenGenerator() : TokenGeneratorInterface
    {
        return new TokenGenerator(
            new Sha256(),
            new ExtractUniqueKeyFromSession(self::DEFAULT_SIGNATURE_KEY_NAME),
            self::DEFAULT_EXPIRATION_TIME,
            SessionMiddleware::SESSION_ATTRIBUTE
        );
    }
}
