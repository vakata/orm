<?php
namespace vakata\orm\test;

class OrmTest extends \PHPUnit_Framework_TestCase
{
	protected static $db       = null;
	protected static $manager  = null;

	public static function setUpBeforeClass() {
		self::$db = new \vakata\database\DB('mysqli://root@127.0.0.1/test');
		self::$db->query("
			CREATE TEMPORARY TABLE IF NOT EXISTS author (
				id int(10) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(255) NOT NULL,
				PRIMARY KEY (id)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");
		self::$db->query("
			CREATE TEMPORARY TABLE IF NOT EXISTS book (
				id int(10) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(255) NOT NULL,
				author_id int(10) unsigned NOT NULL,
				PRIMARY KEY (id)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");
		self::$db->query("
			CREATE TEMPORARY TABLE IF NOT EXISTS tag (
				id int(10) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(255) NOT NULL,
				PRIMARY KEY (id)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");
		self::$db->query("
			CREATE TEMPORARY TABLE IF NOT EXISTS isbn (
				isbn varchar(255) NOT NULL,
				book int(10) unsigned NOT NULL,
				PRIMARY KEY (isbn)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");
		self::$db->query("
			CREATE TEMPORARY TABLE IF NOT EXISTS book_tag (
				book_id int(10) unsigned NOT NULL,
				tag_id int(10) unsigned NOT NULL,
				info varchar(255) NOT NULL,
				PRIMARY KEY (book_id, tag_id)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		self::$db->query('INSERT INTO author VALUES(NULL, ?)', ['Terry Pratchett']);
		self::$db->query('INSERT INTO author VALUES(NULL, ?)', ['Ray Bradburry']);

		self::$db->query('INSERT INTO author VALUES(NULL, ?)', ['Douglas Adams']);
		self::$db->query('INSERT INTO book VALUES(NULL, ?, ?)', ['Equal rites', 1]);
		self::$db->query('INSERT INTO isbn VALUES(?, ?)', ['ER1', 1]);
		self::$db->query('INSERT INTO tag VALUES(NULL, ?)', ['Discworld']);
		self::$db->query('INSERT INTO tag VALUES(NULL, ?)', ['Escarina']);
		self::$db->query('INSERT INTO tag VALUES(NULL, ?)', ['Cooking']);
		self::$db->query('INSERT INTO book_tag VALUES(?, ?, ?)', [1,1,'part of series']);
		self::$db->query('INSERT INTO book_tag VALUES(?, ?, ?)', [1,2,'character']);
	}
	public static function tearDownAfterClass() {
		self::$db->query("DROP TEMPORARY TABLE author");
		self::$db->query("DROP TEMPORARY TABLE book");
		self::$db->query("DROP TEMPORARY TABLE isbn");
		self::$db->query("DROP TEMPORARY TABLE tag");
		self::$db->query("DROP TEMPORARY TABLE book_tag");
	}
	protected function setUp() {
		// self::$db->query("TRUNCATE TABLE test;");
	}
	protected function tearDown() {
		// self::$db->query("TRUNCATE TABLE test;");
	}

	public function testConstruct() {
		self::$manager = new \vakata\orm\Manager(self::$db);
		self::$manager->addDefinitionByTableName('author');
		self::$manager->addDefinitionByTableName('tag');
		self::$manager->addDefinitionByTableName('book');
		self::$manager->addDefinitionByTableName('isbn');

		self::$manager->getDefinition('book')->manyToMany(
			self::$manager->getDefinition('tag'), 
			\vakata\orm\TableDefinition::fromDatabase(self::$db, 'book_tag'),
			'tags', 'book_id', 'tag_id'
		);
		self::$manager->getDefinition('book')->hasOne(
			self::$manager->getDefinition('isbn'), 
			'isbn', 'book'
		);
		self::$manager->getDefinition('author')->hasMany(
			self::$manager->getDefinition('book'), 'books', 'author_id'
		);
		self::$manager->getDefinition('book')->belongsTo(
			self::$manager->getDefinition('author'), 'author', 'author_id'
		);
	}

	public function testCollection() {
		$books = self::$manager->book();
		$this->assertEquals(count($books), 1);
		$this->assertEquals($books[0]->name, 'Equal rites');
	}

	// public function testHasOne() {
	// 	$book   = \vakata\orm\TableDefinition::fromDatabase(self::$db, 'book');
	// 	$author = \vakata\orm\TableDefinition::fromDatabase(self::$db, 'author');
	// 	$author->hasOne($book, 'book', 'author_id');
	// 	$this->assertEquals($author[0]->book->name, 'Equal rites');
	// }

	// public function testBelongsTo() {
	// 	$book   = \vakata\orm\Table::fromDatabase(self::$db, 'book');
	// 	$author = \vakata\orm\Table::fromDatabase(self::$db, 'author');
	// 	$book->belongsTo($author, 'author', 'author_id');
	// 	$this->assertEquals($book[0]->author->name, 'Terry Pratchett');
	// 	$book->filter('author.name', 'Terry Pratchett');
	// 	$this->assertEquals($book->count(), 1);
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testFilterCount() {
	// 	self::$book->filter('tags.name', 'Escarina');
	// 	$this->assertEquals(self::$book[0]->name, 'Equal rites');
	// 	$this->assertEquals(self::$book->count(), 1);
	// 	self::$book->reset();
	// 	self::$book->filter('tags.name', 'Escarina1');
	// 	$this->assertEquals(self::$book->count(), 0);
	// 	self::$book->reset();
	// 	self::$book->filter('tags.name', ['Escarina', 'Discworld']);
	// 	$this->assertEquals(self::$book->count(), 1);
	// 	self::$book->reset();
	// 	self::$author->filter('id', ['beg' => 1, 'end' => 2]);
	// 	$this->assertEquals(self::$author->count(), 2);
	// 	self::$author->reset();
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testReadLoop() {
	// 	foreach(self::$author as $k => $a) {
	// 		$this->assertEquals($k + 1, $a->id);
	// 	}
	// 	foreach(self::$author as $k => $a) {
	// 		$this->assertEquals($k + 1, $a->id);
	// 	}
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testReadIndex() {
	// 	$this->assertEquals(self::$author[0]->name, 'Terry Pratchett');
	// 	$this->assertEquals(self::$author[2]->name, 'Douglas Adams');
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testReadRelations() {
	// 	$this->assertEquals(self::$author[0]->books[0]->name, 'Equal rites');
	// 	$this->assertEquals(self::$author[0]->books[0]->tags[1]->name, 'Escarina');
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testReadChanges() {
	// 	self::$db->query('INSERT INTO author VALUES(NULL, ?)', ['Stephen King']);
	// 	self::$author->reset();
	// 	$this->assertEquals(self::$author[3]->name, 'Stephen King');
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testCreate() {
	// 	self::$author[] = [ 'name' => 'John Resig' ];
	// 	self::$author->save();
	// 	self::$author->reset();
	// 	$this->assertEquals(self::$author[4]->name, 'John Resig');
	// 	$this->assertEquals(self::$author[0]->books[0]->name, 'Equal rites');
	// 	$this->assertEquals(self::$author[0]->books[0]->tags[1]->name, 'Escarina');
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testUpdate() {
	// 	self::$author[0]->name = 'Terry Pratchett, Sir';
	// 	self::$author->save();
	// 	self::$author->reset();
	// 	$this->assertEquals('Terry Pratchett, Sir', self::$author[0]->name);
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testDelete() {
	// 	self::$author[4]->delete();
	// 	self::$author->reset();
	// 	$this->assertEquals(4, self::$author->count());
	// 	$this->assertEquals(null, self::$author[4]);
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testChangePK() {
	// 	$this->assertEquals(self::$author[2]->name, 'Douglas Adams');
	// 	$this->assertEquals(self::$author[2]->id, 3);
	// 	self::$author[2]->fromArray([
	// 		'id' => 42
	// 	]);
	// 	self::$author[2]->save();
	// 	self::$author->reset();
	// 	$this->assertEquals('Douglas Adams', self::$author[3]->name);
	// 	$this->assertEquals(42, self::$author[3]->id);
	// }
	// /**
	//  * @depends testChangePK
	//  */
	// public function testCreateRelationFromDB() {
	// 	$book = self::$book->create([ 'name' => 'The Hitchhiker\'s Guide to the Galaxy', 'author_id' => 42 ]);
	// 	$id = $book->save();
	// 	$id = $id['id'];
	// 	$this->assertEquals($id, 2);
	// 	self::$author->reset();
	// 	$this->assertEquals('The Hitchhiker\'s Guide to the Galaxy', self::$author[3]->books[0]->name);
	// 	return;
	// }
	// /**
	//  * @depends testCreateRelationFromDB
	//  */
	// public function testOrder() {
	// 	$tag = \vakata\orm\Table::fromDatabase(self::$db, 'tag');
	// 	$tag->order('name');
	// 	$this->assertEquals($tag[0]->name, 'Cooking');
	// 	$tag->reset()->order('name DESC');
	// 	$this->assertEquals($tag[0]->name, 'Escarina');

	// 	$book   = \vakata\orm\Table::fromDatabase(self::$db, 'book');
	// 	$author = \vakata\orm\Table::fromDatabase(self::$db, 'author');
	// 	$book->belongsTo($author, 'author', 'author_id');
	// 	$book->order('author.name ASC');
	// 	$this->assertEquals($book[0]->name, 'The Hitchhiker\'s Guide to the Galaxy');
	// 	$book->reset();
	// 	$book->order('name ASC', true);
	// 	$this->assertEquals($book[0]->name, 'Equal rites');
	// }
	// public function testSelect() {
	// 	$author = \vakata\orm\Table::fromDatabase(self::$db, 'author');
	// 	$author->limit(1,1);
	// 	$this->assertEquals($author[0]->name, 'Ray Bradburry');

	// 	$book   = \vakata\orm\Table::fromDatabase(self::$db, 'book');
	// 	$author = \vakata\orm\Table::fromDatabase(self::$db, 'author');
	// 	$book->belongsTo($author, 'author', 'author_id');
	// 	$book->limit(1,0);
	// 	$this->assertEquals('Terry Pratchett, Sir', $book[0]->author->name);
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testRemoveRelation() {
	// 	$this->assertEquals('Discworld', self::$author[0]->books[0]->tags[0]->name);
	// 	unset(self::$author[0]->books[0]->tags[0]);
	// 	self::$author[0]->books->save();
	// 	self::$author->reset();
	// 	$this->assertEquals('Escarina', self::$author[0]->books[0]->tags[0]->name);
	// 	self::$tag->reset();
	// 	$this->assertEquals('Discworld', self::$tag[0]->name);
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testCreateRelation() {
	// 	self::$author[0]->books[0]->tags[] = self::$tag->create(['name' => 'Testing']);
	// 	self::$author[0]->books->save();
	// 	self::$author->reset();
	// 	$this->assertEquals('Testing', self::$author[0]->books[0]->tags[1]->name);
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testEditRelation() {
	// 	self::$author[0]->books[0]->tags[1]->name = 'Modified';
	// 	self::$author[0]->books->save();
	// 	self::$author->reset();
	// 	self::$tag->reset();
	// 	$this->assertEquals('Modified', self::$tag[3]->name);

	// 	self::$author[0]->books[0]->tags[1] = [ 'name' => 'Modified2' ];
	// 	self::$author[0]->books->save();
	// 	self::$author->reset();
	// 	self::$tag->reset();
	// 	$this->assertEquals('Modified2', self::$tag[3]->name);

	// 	self::$author[0]->books[0]->tags[1] = self::$tag->create(['name' => 'Modified3']);
	// 	self::$author[0]->books->save();
	// 	self::$author->reset();
	// 	self::$tag->reset();
	// 	$this->assertEquals('Modified3', self::$tag[4]->name);
	// 	$this->assertEquals('Modified3', self::$author[0]->books[0]->tags[1]->name);
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testComplexPK() {
	// 	$this->assertEquals('', self::$book_tag[0]->info);
	// 	self::$book_tag[0]->info = 'TEMP';
	// 	self::$book_tag->save();
	// 	self::$book_tag->reset();
	// 	$this->assertEquals('TEMP', self::$book_tag[0]->info);
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testComplexPKChange() {
	// 	$this->assertEquals(1, self::$book_tag[0]->book_id);
	// 	$this->assertEquals(2, self::$book_tag[0]->tag_id);
	// 	$this->assertEquals('TEMP', self::$book_tag[0]->info);
	// 	self::$book_tag[0]->book_id = 3;
	// 	self::$book_tag[0]->tag_id = 3;
	// 	self::$book_tag->save();
	// 	self::$book_tag->reset();
	// 	$this->assertEquals(3, self::$book_tag[1]->book_id);
	// 	$this->assertEquals(3, self::$book_tag[1]->tag_id);
	// 	$this->assertEquals('TEMP', self::$book_tag[1]->info);
	// }
	// public function testRow() {
	// 	$book   = \vakata\orm\Table::fromDatabase(self::$db, 'book');
	// 	$author = \vakata\orm\Table::fromDatabase(self::$db, 'author');
	// 	$book->belongsTo($author, 'author', 'author_id');
	// 	$this->assertEquals(
	// 		[
	// 			'id' => 1,
	// 			'name' => 'Equal rites',
	// 			'author_id' => 1,
	// 			'author' => [
	// 				'id' => 1,
	// 				'name' => 'Terry Pratchett, Sir'
	// 			]
	// 		],
	// 		$book[0]->toArray()
	// 	);
	// 	$this->assertEquals(true, json_encode($book[0]) !== false);
	// }
	// public function testAddAdvancedRelation() {
	// 	$book = \vakata\orm\Table::fromDatabase(self::$db, 'book');
	// 	$book->addAdvancedRelation('author', 'author', ['author_id' => 'id'], false);
	// 	$this->assertEquals('Terry Pratchett, Sir', $book[0]->author->name);
	// 	$this->assertEquals(['author'], $book->getRelationKeys());
	// }
	// public function testEditRelation2() {
	// 	$book   = \vakata\orm\Table::fromDatabase(self::$db, 'book');
	// 	$author = \vakata\orm\Table::fromDatabase(self::$db, 'author');
	// 	$book->belongsTo($author, 'author');
	// 	$book[1]->author = ['name'=>'Test'];
	// 	$book->save();
	// 	$book->reset();
	// 	$this->assertEquals('Test', $book[1]->author->name);
	// }
	// /**
	//  * @depends testConstruct
	//  */
	// public function testDeleteAll() {
	// 	self::$book->delete();
	// 	$this->assertEquals(0, self::$db->one('SELECT COUNT(*) FROM book'));
	// }

	// public function testDefinition() {
	// 	$def = self::$author->getDefinition();
	// 	$this->assertEquals(['id'], $def->getPrimaryKey());
	// 	$this->assertEquals(['id', 'name'], $def->getColumns());
	// }
	// /**
	//  * @depends testDeleteAll
	//  */
	// public function testTableArray() {
	// 	$this->assertEquals(true, json_encode(self::$author) !== false);
	// 	self::$author->filter('id', 1);
	// 	$this->assertEquals([['id' => 1, 'name'=>'Terry Pratchett, Sir', 'books' => []]], self::$author->toArray());
	// }
}
