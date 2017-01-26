<?php
namespace vakata\orm;

use \vakata\database\DatabaseInterface;
use \vakata\database\TableQuery;

class Collection extends TableQuery
{
    /**
     * @var Manager
     */
    protected $manager;

    public function __construct(Manager $manager, DatabaseInterface $db, $table)
    {
        parent::__construct($db, $table);
        $this->manager = $manager;
    }
    public function find($key)
    {
        if (!is_array($key)) {
            $key = [ $key ];
        }
        foreach ($this->definition->getPrimaryKey() as $field) {
            $this->filter($field, $key[$field] ?? array_shift($key) ?? null);
        }
        return $this->offsetGet(0);
    }
    public function current()
    {
        if(!($data = parent::current())) {
            return null;
        }
        return $this->manager->instance($this->definition->getName(), $data);
    }
    public function offsetGet($offset)
    {
        if(!($data = parent::offsetGet($offset))) {
            return null;
        }
        return $this->manager->instance($this->definition->getName(), $data);
    }
}
