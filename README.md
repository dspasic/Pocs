# Pocs
Pocs stands for PHP Opcode Cache Status and provides a simple dashboard with some [OpCache](http://php.net/manual/en/book.opcache.php)
informations.

[![Pocs Dashboard](https://raw.githubusercontent.com/dspasic/Pocs/master/share/doc/images/pocs-dashboard.png)](https://raw.githubusercontent.com/dspasic/Pocs/master/share/doc/images/pocs-dashboard.png)

## Secure Pocs
To secure Pocs just create a `pocs.config.php` file in the same directory as the pocs.phar is stored and define the
following constants. 

```php
define('POCS_AUTH_USER', 'USERNAME');
define('POCS_AUTH_PW', 'PASSWORD');
```

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/dspasic/Pocs/issues).
2. Answer questions or fix bugs on the issue tracker.
3. Contribute new features or update the wiki.

> The code contribution process is not very formal. You just need to make sure that you follow the PSR-4, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable.

# Credits

The Pocs is a fork of the project [rlerdorf/opcache-status](https://github.com/rlerdorf/opcache-status).
