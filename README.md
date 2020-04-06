# CakePHP Graylog

Graylog engine for CakePHP 2.x

**Attention:** This CakePHP 2.x plugin is still work in progress. Don't use this
in production.

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
    'append_session' => true,
    'append_post' => true
]);
```

### Further reading

* About [CakePHP 2.x Logging](https://book.cakephp.org/2/en/core-libraries/logging.html)
* About [Graylog 3.x in general](https://docs.graylog.org/en/3.1/index.html)
* About [Graylog Extended Log Format (GELF)](https://docs.graylog.org/en/3.1/pages/gelf.html)
