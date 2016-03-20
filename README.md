# PSR-7 Storage-less HTTP CSRF protection

[![Build Status](https://travis-ci.org/Ocramius/PSR7Csrf.svg)](https://travis-ci.org/Ocramius/PSR7Csrf)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Ocramius/PSR7Csrf/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Ocramius/PSR7Csrf/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/Ocramius/PSR7Csrf/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Ocramius/PSR7Csrf/?branch=master)
[![Packagist](https://img.shields.io/packagist/v/ocramius/psr7-csrf.svg)](https://packagist.org/packages/ocramius/psr7-session)
[![Packagist](https://img.shields.io/packagist/vpre/ocramius/psr7-csrf.svg)](https://packagist.org/packages/ocramius/psr7-session)

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

TODO

### Examples

Simply browse to the `examples` directory in your console, then run

```sh
php -S localhost:9999 index.php
```

Then try accessing `http://localhost:9999`: you should see a counter
that increases at every page refresh

### Configuration options

Please refer to the [configuration documentation](docs/configuration.md).

### Known limitations

Please refer to the [limitations documentation](docs/limitations.md).

### Contributing

Please refer to the [contributing notes](CONTRIBUTING.md).

### License

This project is made public under the [MIT LICENSE](LICENSE).
