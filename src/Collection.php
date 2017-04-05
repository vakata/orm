<?php
namespace vakata\orm;

use \vakata\database\schema\TableQuery;

class Collection implements \Iterator, \ArrayAccess, \Countable
{
    /**
     * @var Manager
     */
    protected $manager;
    /**
     * @var TableQuery
     */
    protected $query;

    public function __construct(Manager $manager, TableQuery $query)
    {
        $this->manager = $manager;
        $this->query = $query;
    }
    public function __clone()
    {
        $this->reset();
    }
    public function find($key)
    {
        if (!is_array($key)) {
            $key = [ $key ];
        }
        foreach ($this->query->getDefinition()->getPrimaryKey() as $field) {
            $this->filter($field, $key[$field] ?? array_shift($key) ?? null);
        }
        return $this->offsetGet(0);
    }
    public function with(string $relation)
    {
        $this->query->with($relation);
        return $this;
    }
    public function filter(string $column, $value)
    {
        $this->query->filter($column, $value);
        return $this;
    }
    public function sort(string $column, bool $desc = false)
    {
        $this->query->sort($column, $desc);
        return $this;
    }
    public function paginate(int $page = 1, int $perPage = 25)
    {
        $this->query->paginate($page, $perPage);
        return $this;
    }
    public function where(string $sql, array $params = [])
    {
        $this->query->where($sql, $params);
        return $this;
    }
    public function order(string $sql, array $params = [])
    {
        $this->query->order($sql, $params);
        return $this;
    }
    public function limit(int $limit, int $offset = 0)
    {
        $this->query->limit($limit, $offset);
        return $this;
    }
    public function count() : int
    {
        return $this->query->count();
    }
    public function reset() {
        $this->query->reset();
        return $this;
    }
    public function current()
    {
        if(!($data = $this->query->current())) {
            return null;
        }
        return $this->manager->instance($this->query->getDefinition()->getName(), $data);
    }
    public function offsetGet($offset)
    {
        if(!($data = $this->query->offsetGet($offset))) {
            return null;
        }
        return $this->manager->instance($this->query->getDefinition()->getName(), $data);
    }

    public function __call($name, $data)
    {
        if (strpos($name, 'filterBy') === 0) {
            return $this->filter(strtolower(substr($name, 8)), $data[0]);
        }
        if (strpos($name, 'sortBy') === 0) {
            return $this->sort(strtolower(substr($name, 6)), $data[0]);
        }
        throw new \BadMethodCallException();
    }

    public function add($entity)
    {
        $this->manager->add($entity, $this->query->getDefinition()->getName());
    }
    public function remove($entity)
    {
        $this->manager->remove($entity, $this->query->getDefinition()->getName());
    }

    public function key()
    {
        return $this->query->key();
    }
    public function rewind()
    {
        return $this->query->rewind();
    }
    public function next()
    {
        return $this->query->next();
    }
    public function valid()
    {
        return $this->query->valid();
    }
    public function offsetExists($offset)
    {
        return $this->query->offsetExists($offset);
    }
    public function offsetUnset($offset)
    {
        return $this->query->offsetUnset($offset);
    }
    public function offsetSet($offset, $value)
    {
        return $this->query->offsetSet($offset, $value);
    }
}
