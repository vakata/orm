# orm

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Code Climate][ico-cc]][link-cc]
[![Tests Coverage][ico-cc-coverage]][link-cc]

A orm abstraction with support for various drivers (mySQL, postgre, oracle, msSQL, sphinx, and even PDO).

## Install

Via Composer

``` bash
$ composer require vakata/orm
```

## Usage

``` php
$db = new vakata\orm\Table('');
echo $db->one("SELECT * FROM table WHERE id = 1");
```

## Testing

``` bash
$ composer test
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email github@vakata.com instead of using the issue tracker.

## Credits

- [vakata][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/vakata/orm.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/vakata/orm/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/vakata/orm.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/vakata/orm.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/vakata/orm.svg?style=flat-square
[ico-cc]: https://img.shields.io/codeclimate/github/vakata/orm.svg?style=flat-square
[ico-cc-coverage]: https://img.shields.io/codeclimate/coverage/github/vakata/orm.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/vakata/orm
[link-travis]: https://travis-ci.org/vakata/orm
[link-scrutinizer]: https://scrutinizer-ci.com/g/vakata/orm/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/vakata/orm
[link-downloads]: https://packagist.org/packages/vakata/orm
[link-author]: https://github.com/vakata
[link-contributors]: ../../contributors
[link-cc]: https://codeclimate.com/github/vakata/orm

