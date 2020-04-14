# CakePHP Graylog

[![License: MIT][license-mit]](LICENSE)
[![Build Status][build-status-master]][travis-ci]
[![Maintainability][maintainability-badge]][maintainability]
[![Test Coverage][coverage-badge]][coverage]

Graylog log engine for CakePHP 3.x

## Usage

```bash
composer require kba-team/cakephp-graylog
```

```php
<?php
\Cake\Core\Configure::write('Log.graylog', [
    'className' => \kbATeam\CakePhpGraylog\Log\Engine\GraylogLog::class,
    'levels' => ['notice', 'info', 'debug', 'warning', 'error', 'critical', 'alert', 'emergency'],
    'host' => 'graylog.example.com',
    'facility' => 'MyAppName',
    'append_backtrace' => true,
    'append_session' => true,
    'append_post' => true
]);
```

Possible configuration parameters are:
* `scheme` Currently TCP or UDP connections to Graylog are supported. Default: `udp`
* `host` The hostname of the Graylog server. Default: `127.0.0.1`
* `port` The port, the Graylog server listens to. Default: `12201`
* `url` A connection URL in format `<scheme>://<host>:<port>`. This will overwrite any other settings.
* `chunk_size` The size of the UDP packages. Default: `\Gelf\Transport\UdpTransport::CHUNK_SIZE_LAN`
* `ssl_options` An instance of `\Gelf\Transport\SslOptions` defining SSL settings for TCP connections. Default: `null`
* `facility` The logging facility. Default: `CakePHP`.
* `append_backtrace` Append a backtrace to the message? Default: `true`
* `append_session` Append the contents of the session to the message? Passwords will be removed according to the list in `password_keys`. Default: `true`
* `append_post` Append the POST parameters to the message? Passwords will be removed according to the list in `password_keys`. Default: `true`
* `password_keys` The values of these keys in the session and post array will be replaced by `********`. Default: `['password', 'new_password', 'old_password', 'current_password']`
* `levels` Array of log level, that will be sent to Graylog. See `\Psr\Log\LogLevel` for all possible values. Default: all of them.

### Further reading

* About [CakePHP 2.x Logging](https://book.cakephp.org/2/en/core-libraries/logging.html)
* About [Graylog 3.x in general](https://docs.graylog.org/en/3.1/index.html)
* About [Graylog Extended Log Format (GELF)](https://docs.graylog.org/en/3.1/pages/gelf.html)

[license-mit]: https://img.shields.io/badge/license-MIT-blue.svg
[travis-ci]: https://travis-ci.org/the-kbA-team/cakephp-graylog
[build-status-master]: https://api.travis-ci.org/the-kbA-team/cakephp-graylog.svg?branch=master
[maintainability-badge]: https://api.codeclimate.com/v1/badges/04abc6d1562d5f628f8a/maintainability
[maintainability]: https://codeclimate.com/github/the-kbA-team/cakephp-graylog/maintainability
[coverage-badge]: https://api.codeclimate.com/v1/badges/04abc6d1562d5f628f8a/test_coverage
[coverage]: https://codeclimate.com/github/the-kbA-team/cakephp-graylog/test_coverage
