<?php
namespace vakata\orm;

use \vakata\database\DBInterface;
use \vakata\database\schema\TableQuery;

/**
 * Manager ORM class
 */
class Manager
{
    /**
     * @var DBInterface
     */
    protected $db;
    /**
     * @var DataMapper[]
     */
    protected $map;

    /**
     * Create an instance
     * @param  DBInterface       $db  the database schema
     */
    public function __construct(DBInterface $db)
    {
        $this->db = $db;
        $this->map = [];
    }
    /**
     * Add a mapper for a specific table
     *
     * @param string $table the table name
     * @param DataMapper $mapper the mapper instance
     * @return $this
     */
    public function registerMapper(string $table, DataMapper $mapper)
    {
        $this->map[$table] = $mapper;
        return $this;
    }
    /**
     * Add a generic mapper for a table name
     *
     * @param string $table the table name
     * @param callable $creator a callable to invoke when a new instance is needed
     * @return $this
     */
    public function registerGenericMapper(string $table, callable $creator)
    {
        return $this->registerMapper($table, new GenericDataMapper($this, $this->db, $table, $creator));
    }
    /**
     * Add a generic mapper for a table name using a class name
     *
     * @param string $table the table name
     * @param string $class the class name to use when creating new instances
     * @return $this
     */
    public function registerGenericMapperWithClassName(string $table, string $class)
    {
        return $this->registerGenericMapper($table, function (array $data = []) use ($class) {
            return new $class;
        });
    }
    /**
     * Is a mapper available for a given table name
     *
     * @param string $table the table name
     * @return boolean
     */
    public function hasMapper(string $table)
    {
        return isset($this->map[$table]);
    }
    /**
     * Get a mapper for a table name, if a mapper is not found a new generic mapper is registered using \StdClass
     *
     * @param string $table
     * @return DataMapper
     */
    public function getMapper(string $table)
    {
        if (!isset($this->map[$table])) {
            $this->registerGenericMapper($table, function (array $data = []) {
                return new class() extends \StdClass implements LazyLoadable {
                    use LazyLoad;
                };
            });
        }
        return $this->map[$table];
    }
    public function __call(string $table, array $args)
    {
        $collection = $this->fromTable($table);
        return !count($args) ?
            $collection :
            $collection->find($args[0]);
    }
    /**
     * Get a repository from a table query
     *
     * @param TableQuery $query
     * @return Repository
     */
    public function fromQuery(TableQuery $query) : Repository
    {
        return new DatabaseRepository($this->getMapper($query->getDefinition()->getName()), $query);
    }
    /**
     * Get a repository for a given table name
     *
     * @param string $table
     * @return Repository
     */
    public function fromTable(string $table) : Repository
    {
        return $this->fromQuery($this->db->table($table));
    }
}
