<?php
namespace vakata\orm\test;

class OrmTest extends \PHPUnit_Framework_TestCase
{
	protected static $db       = null;
	protected static $author   = null;
	protected static $book     = null;
	protected static $tag      = null;
	protected static $book_tag = null;

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
			CREATE TEMPORARY TABLE IF NOT EXISTS book_tag (
				book_id int(10) unsigned NOT NULL,
				tag_id int(10) unsigned NOT NULL,
				info varchar(255) NOT NULL,
				PRIMARY KEY (book_id, tag_id)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");

		self::$db->query('INSERT INTO author VALUES(NULL, ?)', ['Terry Pratchett']);
		self::$db->query('INSERT INTO author VALUES(NULL, ?)', ['Ray Bradburry']);

		self::$author   = new \vakata\orm\Table(self::$db, 'author');
		self::$tag      = new \vakata\orm\Table(self::$db, 'tag');
		self::$book_tag = new \vakata\orm\Table(self::$db, 'book_tag');
		self::$book     = new \vakata\orm\Table(self::$db, 'book');

		self::$book->manyToMany(self::$tag, 'book_tag', 'tags', 'book_id', 'tag_id');
		//self::$book->belongsTo(self::$book, 'author', 'author_id');
		self::$author->hasMany(self::$book, 'books', 'author_id');

		self::$db->query('INSERT INTO author VALUES(NULL, ?)', ['Douglas Adams']);
		self::$db->query('INSERT INTO book VALUES(NULL, ?, ?)', ['Equal rites', 1]);
		self::$db->query('INSERT INTO tag VALUES(NULL, ?)', ['Discworld']);
		self::$db->query('INSERT INTO tag VALUES(NULL, ?)', ['Escarina']);
		self::$db->query('INSERT INTO tag VALUES(NULL, ?)', ['Cooking']);
		self::$db->query('INSERT INTO book_tag VALUES(?, ?, ?)', [1,1,'part of series']);
		self::$db->query('INSERT INTO book_tag VALUES(?, ?, ?)', [1,2,'character']);
	}
	public static function tearDownAfterClass() {
		self::$db->query("DROP TEMPORARY TABLE author");
		self::$db->query("DROP TEMPORARY TABLE book");
		self::$db->query("DROP TEMPORARY TABLE tag");
		self::$db->query("DROP TEMPORARY TABLE book_tag");
	}
	protected function setUp() {
		// self::$db->query("TRUNCATE TABLE test;");
	}
	protected function tearDown() {
		// self::$db->query("TRUNCATE TABLE test;");
	}


	public function testReadLoop() {
		foreach(self::$author as $k => $a) {
			$this->assertEquals($k + 1, $a->id);
		}
		foreach(self::$author as $k => $a) {
			$this->assertEquals($k + 1, $a->id);
		}
	}
	public function testReadIndex() {
		$this->assertEquals(self::$author[0]->name, 'Terry Pratchett');
		$this->assertEquals(self::$author[2]->name, 'Douglas Adams');
	}
	public function testReadRelations() {
		$this->assertEquals(self::$author[0]->books[0]->name, 'Equal rites');
		$this->assertEquals(self::$author[0]->books[0]->tags[1]->name, 'Escarina');
	}
	public function testReadChanges() {
		self::$db->query('INSERT INTO author VALUES(NULL, ?)', ['Stephen King']);
		self::$author->reset();
		$this->assertEquals(self::$author[3]->name, 'Stephen King');
	}
	public function testCreate() {
		self::$author[] = [ 'name' => 'John Resig' ];
		self::$author->save();
		self::$author->reset();
		$this->assertEquals(self::$author[4]->name, 'John Resig');
		$this->assertEquals(self::$author[0]->books[0]->name, 'Equal rites');
		$this->assertEquals(self::$author[0]->books[0]->tags[1]->name, 'Escarina');
	}
	public function testUpdate() {
		self::$author[0]->name = 'Terry Pratchett, Sir';
		self::$author->save();
		self::$author->reset();
		$this->assertEquals('Terry Pratchett, Sir', self::$author[0]->name);
	}
	public function testDelete() {
		self::$author[4]->delete();
		self::$author->reset();
		$this->assertEquals(4, self::$author->count());
		$this->assertEquals(null, self::$author[4]);
	}
	public function testChangePK() {
		$this->assertEquals(self::$author[2]->name, 'Douglas Adams');
		$this->assertEquals(self::$author[2]->id, 3);
		self::$author[2]->fromArray([
			'id' => 42
		]);
		self::$author[2]->save();
		self::$author->reset();
		$this->assertEquals('Douglas Adams', self::$author[3]->name);
		$this->assertEquals(42, self::$author[3]->id);
	}
	/**
	 * @depends testChangePK
	 */
	public function testCreateRelationFromDB() {
		$book = self::$book->create([ 'name' => 'The Hitchhiker\'s Guide to the Galaxy', 'author_id' => 42 ]);
		$id = $book->save();
		$id = $id['id'];
		$this->assertEquals($id, 2);
		self::$author->reset();
		$this->assertEquals('The Hitchhiker\'s Guide to the Galaxy', self::$author[3]->books[0]->name);
		return;
	}
	public function testRemoveRelation() {
		$this->assertEquals('Discworld', self::$author[0]->books[0]->tags[0]->name);
		unset(self::$author[0]->books[0]->tags[0]);
		self::$author[0]->books->save();
		self::$author->reset();
		$this->assertEquals('Escarina', self::$author[0]->books[0]->tags[0]->name);
		self::$tag->reset();
		$this->assertEquals('Discworld', self::$tag[0]->name);
	}
	public function testCreateRelation() {
		self::$author[0]->books[0]->tags[] = self::$tag->create(['name' => 'Testing']);
		self::$author[0]->books->save();
		self::$author->reset();
		$this->assertEquals('Testing', self::$author[0]->books[0]->tags[1]->name);
	}
	public function testEditRelation() {
		self::$author[0]->books[0]->tags[1]->name = 'Modified';
		self::$author[0]->books->save();
		self::$author->reset();
		self::$tag->reset();
		$this->assertEquals('Modified', self::$tag[3]->name);
	}
	public function testComplexPK() {
		$this->assertEquals('', self::$book_tag[0]->info);
		self::$book_tag[0]->info = 'TEMP';
		self::$book_tag->save();
		self::$book_tag->reset();
		$this->assertEquals('TEMP', self::$book_tag[0]->info);
	}
	public function testComplexPKChange() {
		$this->assertEquals(1, self::$book_tag[0]->book_id);
		$this->assertEquals(2, self::$book_tag[0]->tag_id);
		$this->assertEquals('TEMP', self::$book_tag[0]->info);
		self::$book_tag[0]->book_id = 3;
		self::$book_tag[0]->tag_id = 3;
		self::$book_tag->save();
		self::$book_tag->reset();
		$this->assertEquals(3, self::$book_tag[1]->book_id);
		$this->assertEquals(3, self::$book_tag[1]->tag_id);
		$this->assertEquals('TEMP', self::$book_tag[1]->info);
	}
}
