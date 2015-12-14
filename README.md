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
// first you need a database instance
$db = new \vakata\database\DB('mysqli://root@127.0.0.1/test');

// then you can create the table object
$books = new \vakata\orm\Table($db, 'books'); // assuming there is a books table
foreach ($books as $v) {
    // iterate over the books
}
$books[0]; // get the first book

// you can also filter and order
$books->filter('pages', 10)->order('name DESC');
foreach ($books) { }

// do not forget to reset if you want to change filters
$books->reset(); // clears filters and ordering

// the power comes from relations
$authors = new \vakata\orm\Table($db, 'authors');
// create a 1-to-1 relation by specifying a table object, relation name and column name (on the books table)
$book->belongsTo($authors, 'author', 'author_id');
// now you can access the relation by its name
$books[0]->author->name;
// you can also filter by it
$books->filter('author.name', 'Terry Pratchett');
// or order
$books->order('author.name');

// there is also are hasOne / hasMany / manyToMany relations
$authors->hasMany($books, 'books', 'author_id');
// now you can use
$author[0]->books[1]->name;

// manyToMany relations require a pivot table
$tags = new \vakata\orm\Table($db, 'tags');
// create using: table instance, pivot table name, relation name, own id, foreign id
$books->manyToMany($tags, 'book_tag', 'tags', 'book_id', 'tag_id');

// you can also create, edit or delete relations
$books[0]->tags[] = $tags->create(['name' => 'Testing']);
$books->save();
$books[0]->tags[0]->name = 'New name';
$books->save();
unset($books[0]->tags[0]);
$books->save();
```

Read more in the [API docs](docs/README.md)

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

