<?php
namespace vakata\orm\test;

class Author extends \vakata\orm\Row
{
}

class OrmTest extends \PHPUnit_Framework_TestCase
{
    protected static $db       = null;
    protected static $schema   = null;
    protected static $manager  = null;

    public static function setUpBeforeClass() {
        $sql = file_get_contents(__DIR__ . '/dump.sql');
        $sql = explode(';', $sql);
        self::$db = new \vakata\database\DB('mysqli://root@127.0.0.1/test');
        self::$db->query("SET FOREIGN_KEY_CHECKS = 0");
        self::$db->query("DROP TABLE IF EXISTS author");
        self::$db->query("DROP TABLE IF EXISTS book");
        self::$db->query("DROP TABLE IF EXISTS tag");
        self::$db->query("DROP TABLE IF EXISTS book_tag");
        foreach ($sql as $query) {
            if (strlen(trim($query, " \t\r\n"))) {
                self::$db->query($query);
            }
        }
    }
    public static function tearDownAfterClass() {
        self::$db->query("SET FOREIGN_KEY_CHECKS = 0");
        self::$db->query("DROP TABLE IF EXISTS author");
        self::$db->query("DROP TABLE IF EXISTS book");
        self::$db->query("DROP TABLE IF EXISTS tag");
        self::$db->query("DROP TABLE IF EXISTS book_tag");
    }
    protected function setUp() {
        // self::$db->query("TRUNCATE TABLE test;");
    }
    protected function tearDown() {
        // self::$db->query("TRUNCATE TABLE test;");
    }

    public function testCollection() {
        $manager = new \vakata\orm\Manager(self::$db);

        $books = $manager->book();
        $this->assertEquals(count($books), 1);
        $this->assertEquals($books[0]->name, 'Equal rites');
    }
    public function testRelations() {
        $manager = new \vakata\orm\Manager(self::$db);

        $books = $manager->book();
        $this->assertEquals('Terry Pratchett', $books[0]->author[0]->name);
        $this->assertEquals(2, count($books[0]->tag));
        $this->assertEquals('Terry Pratchett', $books[0]->author[0]->book[0]->tag[0]->book[0]->author[0]->name);
    }

    public function testRelationsWith() {
        $manager = new \vakata\orm\Manager(self::$db);

        $books = $manager->book()->with('author');
        $this->assertEquals($books[0]->author[0]->name, 'Terry Pratchett');
    }

    public function testFilter() {
        $manager = new \vakata\orm\Manager(self::$db);

        $this->assertEquals(count($manager->book()->filter('name', 'Equal rites')), 1);
        $this->assertEquals(count($manager->book()->filter('name', 'Not found')), 0);
        $this->assertEquals(count($manager->book()->filter('author.name', 'Terry Pratchett')), 1);
        $this->assertEquals(count($manager->book()->filter('author.name', 'Douglas Adams')), 0);
        $this->assertEquals(count($manager->book()->filter('tag.name', 'Escarina')), 1);
        $this->assertEquals(count($manager->book()->filter('tag.name', 'Discworld')), 1);
        $this->assertEquals(count($manager->book()->filter('tag.name', 'None')), 0);
    }

    public function testReadLoop() {
        $manager = new \vakata\orm\Manager(self::$db);
        $author = $manager->author();
        foreach($author as $k => $a) {
            $this->assertEquals($k + 1, $a->id);
        }
        foreach($author as $k => $a) {
            $this->assertEquals($k + 1, $a->id);
        }
    }
    public function testReadIndex() {
        $manager = new \vakata\orm\Manager(self::$db);
        $author = $manager->author();
        $this->assertEquals($author[0]->name, 'Terry Pratchett');
        $this->assertEquals($author[2]->name, 'Douglas Adams');
    }
    public function testReadRelations() {
        $manager = new \vakata\orm\Manager(self::$db);
        $author = $manager->author();
        $this->assertEquals($author[0]->book[0]->name, 'Equal rites');
        $this->assertEquals($author[0]->book[0]->tag[1]->name, 'Escarina');
    }
    public function testReadChanges() {
        self::$db->query('INSERT INTO author VALUES(NULL, ?)', ['Stephen King']);
        $manager = new \vakata\orm\Manager(self::$db);
        $author = $manager->author();
        $this->assertEquals($author[3]->name, 'Stephen King');
    }

    public function testCreate() {
        $manager = new \vakata\orm\Manager(self::$db);
        $manager->addClass(Author::CLASS, 'author');

        $author = $manager->author();

        $resig = new Author();
        $resig->name = 'John Resig';
        $manager->save($resig);

        $this->assertEquals($author[4]->name, 'John Resig');
        $this->assertEquals(self::$db->one('SELECT name FROM author WHERE id = 5'), 'John Resig');
        $this->assertEquals($author[0]->book[0]->name, 'Equal rites');
        $this->assertEquals($author[0]->book[0]->tag[1]->name, 'Escarina');
    }
    public function testManagerCreate() {
        $manager = new \vakata\orm\Manager(self::$db);
        $tag = new \StdClass();
        $tag->id = null;
        $tag->name = 'TEST'; // $manager->create('tag', [ 'name' => 'TEST' ]);
        $manager->save($tag, false, 'tag');
        $this->assertEquals($manager->tag()[3]->name, 'TEST');
    }
    public function testUpdate() {
        $manager = new \vakata\orm\Manager(self::$db);
        $author = $manager->author();
        $author[0]->name = 'Terry Pratchett, Sir';
        $manager->save($author[0]);
        $this->assertEquals($manager->author()[0]->name, 'Terry Pratchett, Sir');
        $this->assertEquals(self::$db->one('SELECT name FROM author WHERE id = 1'), 'Terry Pratchett, Sir');
    }
    public function testDelete() {
        $manager = new \vakata\orm\Manager(self::$db);
        $author = $manager->author();
        $manager->delete($author[4]);
        $this->assertEquals(count($manager->author()), 4);
        $this->assertEquals(self::$db->one('SELECT COUNT(id) FROM author'), 4);
        $manager->delete($manager->book()[0]);
        $this->assertEquals(count($manager->book()), 0);
        $this->assertEquals(count($author[0]->book), 0);
        $this->assertEquals(self::$db->one('SELECT COUNT(id) FROM book'), 0);
        $this->assertEquals(self::$db->one('SELECT COUNT(*) FROM book_tag'), 0);
    }
    public function testChangePK() {
        $manager = new \vakata\orm\Manager(self::$db);
        $author = $manager->author();
        $this->assertEquals($author[2]->name, 'Douglas Adams');
        $this->assertEquals($author[2]->id, 3);
        $author[2]->id = 42;
        $manager->save($author[2]);
        $this->assertEquals('Douglas Adams', $manager->author()[3]->name);
        $this->assertEquals(42, $manager->author()[3]->id);
    }
    public function testCreateRelationFromDB() {
        $manager = new \vakata\orm\Manager(self::$db);
        $author = $manager->author();
        self::$db->query('INSERT INTO book VALUES(NULL, ?, ?)', ['The Hitchhiker\'s Guide to the Galaxy', 42]);
        $this->assertEquals('The Hitchhiker\'s Guide to the Galaxy', $author[3]->book[0]->name);
    }
}
