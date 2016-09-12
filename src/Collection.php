<?php
namespace vakata\orm;

class Collection implements \Iterator, \ArrayAccess, \Countable
{
    protected $class;
    protected $query;
    protected $single;
    protected $manager;
    protected $current;

    public function __construct(Query $query, Manager $manager, string $class, array $current = null, bool $single = false)
    {
        $this->class = $class;
        $this->query = $query;
        $this->single = $single;
        $this->manager = $manager;
        $this->current = $current;
    }

    public function count() : int
    {
        return $this->query->count();
    }
    public function reset() : TableInterface
    {
        $this->query->reset();
        $this->current = null;
        return $this;
    }

    public function with(string $relation) : Collection
    {
        $this->query->with($relation);
        $this->current = null;
        return $this;
    }
    public function filter(string $column, $value) : Collection
    {
        $this->query->filter($column, $value);
        $this->current = null;
        return $this;
    }
    public function sort(string $column, bool $desc = false) : Collection
    {
        $this->query->sort($column, $desc);
        $this->current = null;
        return $this;
    }
    public function paginate(int $page = 1, int $perPage = 25) : Collection
    {
        $this->query->paginate($page, $perPage);
        $this->current = null;
        return $this;
    }
    public function where(string $sql, array $params = []) : Collection
    {
        $this->query->where($sql, $params);
        $this->current = null;
        return $this;
    }
    public function order(string $sql, array $params = []) : Collection
    {
        $this->query->order($sql, $params);
        $this->current = null;
        return $this;
    }
    public function limit(int $limit, int $offset = 0) : Collection
    {
        $this->query->limit($limit, $offset);
        $this->current = null;
        return $this;
    }

    public function get()
    {
        if ($this->single) {
            return $this->offsetGet(0);
        }
        $temp = [];
        foreach ($this as $v) {
            $temp[] = $v;
        }
        return $temp;
    }

    // array stuff - collection handling
    public function offsetGet($offset)
    {
        if ($this->current === null) {
            $this->current = $this->query->select();
        }
        if (!isset($this->current[$offset])) {
            return null;
        }
        $item = $this->current[$offset];
        if (is_array($item)) {
            return $this->current[$offset] = $this->manager->entity($this->class, $item, $item, $this->query->getDefinition());
        }
        return $this->current[$offset];
    }
    public function offsetSet($offset, $value)
    {
        throw new ORMException('Invalid call to offsetSet');
    }
    public function offsetExists($offset)
    {
        if ($this->current === null) {
            $this->current = $this->query->select();
        }
        return isset($this->current[$offset]);
    }
    public function offsetUnset($offset)
    {
        throw new ORMException('Invalid call to offsetUnset');
    }
    public function current()
    {
        if ($this->current === null) {
            $this->current = $this->query->select();
        }
        $item = current($this->current);
        if (is_array($item)) {
            return $this->current[$this->key()] = $this->manager->entity($this->class, $item, $item, $this->query->getDefinition());
        }
        return $item;
    }
    public function key()
    {
        if ($this->current === null) {
            $this->current = $this->query->select();
        }
        return key($this->current);
    }
    public function next()
    {
        if ($this->current === null) {
            $this->current = $this->query->select();
        }
        return next($this->current);
    }
    public function rewind()
    {
        if ($this->current === null) {
            $this->current = $this->query->select();
        }
        reset($this->current);
    }
    public function valid()
    {
        if ($this->current === null) {
            $this->current = $this->query->select();
        }
        return current($this->current) !== false;
    }
}
