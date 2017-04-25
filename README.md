# orm

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Code Climate][ico-cc]][link-cc]
[![Tests Coverage][ico-cc-coverage]][link-cc]

A orm abstraction with support for various drivers.

## Install

Via Composer

``` bash
$ composer require vakata/orm
```

## Usage

``` php
// first you need a database instance
$db = new \vakata\database\DB('mysql://root@127.0.0.1/test');

// then you can create the manager object
$manager = new \vakata\orm\Manager($db);

// assuming there is a book table with a name column
foreach ($manager->book() as $book) {
    echo $book->name . "\n";
}
// you could of course filter and order
foreach ($manager->book()->filter('year', 2016)->sort('name') as $book) {
    // iterate over the books from 2016
}

// if using mySQL foreign keys are automatically detected
// for example if there is an author table and the book table references it
foreach ($manager->book() as $book) {
    echo $book->author->name . "\n";
}

// you can solve the n+1 queries problem like this
foreach ($manager->fromQuery($db->book()->with('author')) as $book) {
    echo $book->author->name . "\n";
}

// provided there is a linking table book_tag and a tag table and each book has many tags you can do this
foreach ($manager->book()->with('author') as $book) {
    echo $book->tag[0]->name . "\n"; // the name of the first tag which the current book has
}

// which means you can do something like this
echo $manager->book()[0]->author->book[0]->tag[0]->book[0]->author->name;

// as for removing objects
$authors = $manager->author();
$author = $authors[0];
$authors->remove($author);

// you can also create new objects
$book = new \StdClass();
$book->name = 'Test';
$manager->books()->add($book);

// you can also use your own classes
// just make sure you provide getters and setters for all table columns and relations
class Author
{
    protected $data = [];
    public function __get($key) { return $this->data[$key] ?? null; }
    public function __set($key, $value) { $this->data[$key] = $value; }
}
$manager->registerGenericMapperWithClassName('author', Author::class); // you can also add custom mappers (check the docs)
$author = $manager->author()[0]; // this is an Author instance

// as for changing objects
// one way is to do it manually
$authors = $manager->author();
$author = $authors[0];
$author->name = "Test";
$manager->getMapper('author')->update($author);

// or use the unit of work manager and have all changes automatically saved
$manager = new \vakata\orm\UnitOfWorkManager($db, new \vakata\orm\UnitOfWork($db));
$book = new Book();
$book->name = "Book name";
$book->author = $manager->author()->find(1);
$tag = new Tag('tag');
$book->tags = $tag;
$book->author->name = "Changed name";
$manager->books()->add($book);
$manager->tags()->add($tag);
// all you need to do is call this line
$manager->save();
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

