# Pocs
Pocs stands for PHP Opcode Cache Status and provides a simple dashboard with some (OpCache)[http://php.net/manual/en/book.opcache.php]
status information's.

## Secure Pocs
To secure Pocs just create a `pocs.config.php` file in the same directory as the pocs.phar is stored and define the
following constants. 

```php
define('POCS_AUTH_USER', 'USERNAME');
define('POCS_AUTH_PW', 'PASSWORD');
```
