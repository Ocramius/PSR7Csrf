<?php

declare(strict_types=1);

namespace PSR7Csrf\Example;

use Dflydev\FigCookies\SetCookie;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Psr\Http\Message\ResponseInterface;
use PSR7Csrf\Factory;
use PSR7Session\Http\SessionMiddleware;
use Zend\Expressive\Application;
use Zend\Expressive\Router\FastRouteRouter;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/../src/PSR7Csrf/Factory.php';
require_once __DIR__ . '/../src/PSR7Csrf/CSRFCheckerMiddleware.php';

$app = new Application(new FastRouteRouter());

$app->pipeRoutingMiddleware();
// note: following instantiation is done manually, as you will unlikely have HTTPS in a dev environment
$app->pipe(new SessionMiddleware(
    new Sha256(),
    'c9UA8QKLSmDEn4DhNeJIad/4JugZd/HvrjyKrS0jOes=', // signature key (important: change this to your own)
    'c9UA8QKLSmDEn4DhNeJIad/4JugZd/HvrjyKrS0jOes=', // verification key (important: change this to your own)
    SetCookie::create('an-example-cookie-name')
        ->withSecure(false) // false on purpose, unless you have https locally
        ->withHttpOnly(true),
    new Parser(),
    1200
));
$app->pipe(Factory::createDefaultCSRFCheckerMiddleware());
$app->pipeDispatchMiddleware();

$tokenGenerator = Factory::createDefaultTokenGenerator();

$app->get('/', function ($request, ResponseInterface $response) use ($tokenGenerator) {
    $response
        ->getBody()
        ->write(
            '<form method="post" action="/post">'
            . '<input type="submit"/>'
            . '<input type="hidden" name="csrf_token" value="'
            . $tokenGenerator($request)
            . '"/>'
            . '</form>'
        );

    return $response;
});

$app->post('/post', function ($request, ResponseInterface $response) {
    $response
        ->getBody()
        ->write('It works!');

    return $response;
});

$app->run();
