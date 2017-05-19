<?php
namespace vakata\orm;

use \vakata\database\schema\TableQuery;

class DatabaseRepository implements Repository
{
    /**
     * @var DataMapper
     */
    protected $mapper;
    /**
     * @var TableQuery
     */
    protected $query;
    /**
     * @var bool
     */
    protected $consumed = false;
    /**
     * @var bool
     */
    protected $modified = false;

    public function __construct(DataMapper $mapper, TableQuery $query)
    {
        $this->mapper = $mapper;
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
    public function filter(string $column, $value) : Repository
    {
        $this->query->filter($column, $value);
        return $this;
    }
    public function reject(string $column, $value) : Repository
    {
        $this->query->filter($column, $value, true);
        return $this;
    }
    public function sort(string $column, bool $desc = false) : Repository
    {
        $this->query->sort($column, $desc);
        return $this;
    }
    public function limit(int $limit, int $offset = 0) : Repository
    {
        $this->query->limit($limit, $offset);
        return $this;
    }
    public function count() : int
    {
        return $this->query->count();
    }
    public function reset() : Repository
    {
        $this->query->reset();
        return $this;
    }
    public function current()
    {
        if(!($data = $this->query->getIterator()->current())) {
            return null;
        }
        $this->consumed = true;
        return $this->mapper->entity($data);
    }
    public function offsetGet($offset)
    {
        if(!($data = $this->query->offsetGet($offset))) {
            return null;
        }
        $this->consumed = true;
        return $this->mapper->entity($data);
    }

    public function append($entity) : Repository
    {
        $this->modified = true;
        $this->mapper->insert($entity);
        return $this;
    }
    public function change($entity) : Repository
    {
        $this->modified = true;
        $this->mapper->update($entity);
        return $this;
    }
    public function remove($entity) : Repository
    {
        $this->modified = true;
        $this->mapper->delete($entity);
        return $this;
    }

    public function key()
    {
        return $this->query->getIterator()->key();
    }
    public function rewind()
    {
        return $this->query->getIterator()->rewind();
    }
    public function next()
    {
        return $this->query->getIterator()->next();
    }
    public function valid()
    {
        return $this->query->getIterator()->valid();
    }
    public function offsetExists($offset)
    {
        return $this->query->offsetExists($offset);
    }
    public function offsetUnset($offset)
    {
        $this->remove($this->offsetGet($offset));
    }
    public function offsetSet($offset, $value)
    {
        if ($offset !== null) {
            throw new \BadMethodCallException();
        }
        $this->append($value);
    }

    public function isConsumed() : bool
    {
        return $this->consumed;
    }
    public function isModified() : bool
    {
        return $this->modified;
    }

    public function toArray($entity) : array
    {
        return $this->mapper->toArray($entity, false);
    }

    public function search(string $q) : Repository
    {
        $sql = [];
        $par = [];
        $table = $this->query->getDefinition()->getName();
        foreach ($this->query->getDefinition()->getFullColumns() as $name => $column) {
            if ($column->getBasicType() === 'text') {
                $sql[] = $table . '.' . $name . ' = ?';
                $par[] = $q;
                $sql[] = $table . '.' . $name . ' LIKE ?';
                $par[] = '%' . str_replace(['%', '_'], ['\\%','\\_'], $q) . '%';
            }
        }
        if (count($sql)) {
            $this->query->where("(".implode(" OR ", $sql).")", $par);
        }
        return $this;
    }
}
