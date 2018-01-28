This is a list of changes/improvements that were introduced in PSR7Csrf

## 2.0.0

- This release aligns the `PSR7Csrf\CSRFCheckerMiddleware` implementation to
  the [PSR-15 `php-fig/http-server-middleware`](https://github.com/php-fig/http-server-middleware/tree/1.0.0)
  specification.
   
  This means that the signature of `PSR7Csrf\CSRFCheckerMiddleware`
  changed, and therefore you need to look for usages of this class and verify
  if the new signature is compatible with your API

  Specifically, `PSR7Csrf\CSRFCheckerMiddleware#__invoke()` was removed.
  
- The minimum supported PHP version has been raised to `7.1.0`

- the `PSR7Csrf\Factory::createDefaultCSRFCheckerMiddleware()` method now has
  a mandatory argument, which is the response to be produced in case of failed
  CSRF validation. This argument is mandatory, since PSR7Csrf won't couple you
  to a specific PSR-7 implementation.

## 1.0.2

### Fixed

- Allow installation of [PSR7Session](https://github.com/Ocramius/PSR7Session)
  [2.0.0](https://github.com/Ocramius/PSR7Session/releases/tag/2.0.0) [#2](https://github.com/Ocramius/PSR7Csrf/pull/1)

## 1.0.1

### Fixed

- Minor wording issues in [`README.md`](README.md] [#1](https://github.com/Ocramius/PSR7Csrf/pull/1)
