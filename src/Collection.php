<?php
namespace vakata\orm;

use \vakata\database\DBInterface;
use \vakata\database\schema\TableQuery;

class Collection extends TableQuery
{
    /**
     * @var Manager
     */
    protected $manager;
    /**
     * @var string
     */
    protected $className;

    public function __construct(DBInterface $db, string $table, Manager $manager, string $className)
    {
        parent::__construct($db, $table);
        $this->manager = $manager;
        $this->className = $className;
    }
    public function find($key)
    {
        if (!is_array($key)) {
            $key = [ $key ];
        }
        foreach ($this->getDefinition()->getPrimaryKey() as $field) {
            $this->filter($field, $key[$field] ?? array_shift($key) ?? null);
        }
        return $this->offsetGet(0);
    }
    public function current()
    {
        if(!($data = parent::current())) {
            return null;
        }
        return $this->manager->instance($this->className, $data);
    }
    public function offsetGet($offset)
    {
        if(!($data = $this->offsetGet($offset))) {
            return null;
        }
        return $this->manager->instance($this->className, $data);
    }

    public function add($entity)
    {
        $this->manager->add($entity, $this->className);
    }
    public function remove($entity)
    {
        $this->manager->remove($entity, $this->className);
    }
}
