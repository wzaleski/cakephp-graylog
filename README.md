# CakePHP Graylog

[![License: MIT][license-mit]](LICENSE)
[![Build Status][build-status-master]][travis-ci]
[![Maintainability][maintainability-badge]][maintainability]
[![Test Coverage][coverage-badge]][coverage]

Graylog engine for CakePHP 2.x

## Usage

```bash
composer require kba-team/cakephp-graylog:dev-master
```

```php
<?php
CakePlugin::load('Graylog');
CakeLog::config('graylog', [
    'engine' => 'Graylog.Graylog',
    'types' => ['warning', 'error', 'critical', 'alert', 'emergency'],
    'host' => 'graylog.example.com',
    'facility' => 'MyAppName',
    'append_backtrace' => true,
    'append_session' => true,
    'append_post' => true
]);
```

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
