# CakePHP Graylog

[![License: MIT][license-mit]](LICENSE)
[![Packagist Version][packagist-badge]][packagist]
[![Build Status][build-status-cake2]][travis-ci]
[![Maintainability][maintainability-badge]][maintainability]
[![Test Coverage][coverage-badge]][coverage]

Graylog log engine for CakePHP 2.x

## Usage

```bash
composer require kba-team/cakephp-graylog
```

```php
<?php
CakePlugin::load('Graylog');
CakeLog::config('graylog', [
    'engine' => 'Graylog.Graylog',
    'types' => ['warning', 'error', 'critical', 'alert', 'emergency'], //PSR log levels
    'host' => 'graylog.example.com',
    'port' => 12201,
    'facility' => 'MyAppName',
    'append_backtrace' => true, //append a complete backtrace to the message body
    'append' => [
        //append the contents of $_POST JSON encoded to the message body
        'POST' => static function () {
             if (!empty($_POST)) {
                $obfuscator = new \kbATeam\GraylogUtilities\Obfuscator();
                //Replace the value of all keys named 'password' with 'xxx'.
                $obfuscator->addKey('password');
                $obfuscator->setObfuscationString('xxx');
                //JSON encode the POST variable for readability.
                return json_encode(
                    $obfuscator->obfuscate($_POST),
                    JSON_PRETTY_PRINT
                );
             }
             return null;
        }
    ],
    'additional' => [
        //Add field 'current_user' to the GELF message.
        'current_user' => static function () {
             return AuthComponent::user('username');
        }
    ]
]);
```

Possible configuration parameters are:
* `scheme` Currently TCP or UDP connections to Graylog are supported. Default: `udp`
* `host` The hostname of the Graylog server. Default: `127.0.0.1`
* `port` The port, the Graylog server listens to. Default: `12201`
* `url` A connection URL in format `<scheme>://<host>:<port>`. This will overwrite any other settings.
* `ignore_transport_errors` Ignore transport errors Default: `true`
* `chunk_size` The size of the UDP packages. Default: `\Gelf\Transport\UdpTransport::CHUNK_SIZE_LAN`
* `ssl_options` An instance of `\Gelf\Transport\SslOptions` defining SSL settings for TCP connections. Default: `null`
* `facility` The logging facility. Default: `CakePHP`.
* `append_backtrace` Append a backtrace to the message body? Default: `true`
* `append` Array of anonymous functions. Their return strings get appended to the message body.
* `additional` Array of anonymous functions. Their return values get added as additional fields to the GELF message.
* `types` Array of log types, that will be sent to Graylog. See `\Psr\Log\LogLevel` for all possible values. Default: all of them.

### Further reading

* About [CakePHP 2.x Logging](https://book.cakephp.org/2/en/core-libraries/logging.html)
* About [Graylog 3.x in general](https://docs.graylog.org/en/3.1/index.html)
* About [Graylog Extended Log Format (GELF)](https://docs.graylog.org/en/3.1/pages/gelf.html)

[license-mit]: https://img.shields.io/badge/license-MIT-blue.svg
[packagist-badge]: https://img.shields.io/packagist/v/kba-team/cakephp-graylog
[packagist]: https://packagist.org/packages/kba-team/cakephp-graylog
[travis-ci]: https://travis-ci.org/the-kbA-team/cakephp-graylog
[build-status-cake2]: https://api.travis-ci.org/the-kbA-team/cakephp-graylog.svg?branch=cake2
[maintainability-badge]: https://api.codeclimate.com/v1/badges/04abc6d1562d5f628f8a/maintainability
[maintainability]: https://codeclimate.com/github/the-kbA-team/cakephp-graylog/maintainability
[coverage-badge]: https://api.codeclimate.com/v1/badges/04abc6d1562d5f628f8a/test_coverage
[coverage]: https://codeclimate.com/github/the-kbA-team/cakephp-graylog/test_coverage
