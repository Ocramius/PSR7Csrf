# PSR-7 Storage-less HTTP CSRF protection

[![Build Status](https://travis-ci.org/Ocramius/PSR7Csrf.svg)](https://travis-ci.org/Ocramius/PSR7Csrf)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Ocramius/PSR7Csrf/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Ocramius/PSR7Csrf/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Ocramius/PSR7Csrf/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Ocramius/PSR7Csrf/?branch=master)
[![Packagist](https://img.shields.io/packagist/v/ocramius/psr7-csrf.svg)](https://packagist.org/packages/ocramius/psr7-csrf)
[![Packagist](https://img.shields.io/packagist/vpre/ocramius/psr7-csrf.svg)](https://packagist.org/packages/ocramius/psr7-csrf)

**PSR7Csrf** is a [PSR-7](http://www.php-fig.org/psr/psr-7/)
[middleware](https://mwop.net/blog/2015-01-08-on-http-middleware-and-psr-7.html) that enables
[CSRF](https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)) protection for PSR-7 based applications.

Instead of storing tokens in the session, PSR7Csrf simply uses JWT tokens,
which can be verified, signed and have a specific lifetime on their own.

This storage-less approach prevents having to load tokens from a session
or from a database, and simplifies the entire UI workflow: tokens are
valid as long as their signature and expiration date holds.

### Installation

```sh
composer require ocramius/psr7-csrf
```

### Usage

The simplest usage is based on defaults. It assumes that you have
a configured PSR-7 compatible application that supports piping
middlewares, and it also requires you to run [PSR7Session](https://github.com/Ocramius/PSR7Session).

In a [`zendframework/zend-expressive`](https://github.com/zendframework/zend-expressive)
application, the setup would look like the following:

```php
$app = \Zend\Expressive\AppFactory::create();

$app->pipe(\PSR7Session\Http\SessionMiddleware::fromSymmetricKeyDefaults(
    'mBC5v1sOKVvbdEitdSBenu59nfNfhwkedkJVNabosTw=', // replace this with a key of your own (see PSR7Session docs)
    1200 // 20 minutes session duration
));

$app->pipe(\PSR7Csrf\Factory::createDefaultCSRFCheckerMiddleware());
```

This setup will require that any requests that are not `GET`, `HEAD` or
`OPTIONS` contain a `csrf_token` in the request body parameters (JSON
or URL-encoded).

You can generate the CSRF token for any form like following:

```php
$tokenGenerator = \PSR7Csrf\Factory::createDefaultTokenGenerator();

$app->get('/get', function ($request, $response) use ($tokenGenerator) {
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

$app->post('/post', function ($request, $response) {
    $response
        ->getBody()
        ->write('It works!');

    return $response;
});
```

### Examples

```sh
composer install # install at the root of this package first!
cd examples
composer install
php -S localhost:9999 index.php
```

Then try accessing `http://localhost:9999`: you should see a simple
submission form.

If you try modifying the submitted CSRF token (which is in a hidden
form field), then the `POST` request will fail.

### Known limitations

Please refer to the [known limitations of PSR7Session](https://github.com/Ocramius/PSR7Session/blob/master/docs/limitations.md).

Also, this component does *NOT* prevent double-form-submissions: it
merely prevents CSRF attacks from third parties. As long as the CSRF
token is valid, it can be reused over multiple requests.

### Contributing

Please refer to the [contributing notes](CONTRIBUTING.md).

### License

This project is made public under the [MIT LICENSE](LICENSE).
