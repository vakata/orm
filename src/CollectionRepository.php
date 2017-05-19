<?php
namespace vakata\orm;

use \vakata\collection\Collection;

class CollectionRepository implements Repository
{
    /**
     * @var Collection
     */
    protected $collection;
    /**
     * @var Collection
     */
    protected $original;
    /**
     * @var array
     */
    protected $id = false;
    /**
     * @var callable
     */
    protected $search = null;

    public function __construct(Collection $collection, $id, callable $search = null)
    {
        $this->collection = $collection;
        $this->original = clone $collection;
        $this->id = is_array($id) ? $id : [$id];
        $this->search = $search ?? function ($v) {
            return true;
        };
    }
    public function __clone()
    {
        $this->reset();
    }
    public function find($key)
    {
        $where = [];
        foreach ($this->id as $field) {
            $where[$field] = $key[$field] ?? array_shift($key) ?? null;
        }
        return $this->collection->where($where)[0];
    }
    public function filter(string $column, $value) : Repository
    {
        $where = [];
        $where[$column] = $value;
        $this->collection = $this->collection->where($where);
        return $this;
    }
    public function reject(string $column, $value) : Repository
    {
        $this->collection = $this->collection->reject(function ($v) use ($column, $value) {
            $strict = false;
            $vv = is_object($v) ? (isset($v->{$column}) ? $v->{$column} : null) : (isset($v[$column]) ? $v[$column] : null);
            if (!$vv || ($strict && $vv !== $value) || (!$strict && $vv != $value)) {
                return false;
            }
            return true;
        });
        return $this;
    }
    public function sort(string $column, bool $desc = false) : Repository
    {
        $this->collection = $this->collection->sort(function ($a, $b) use ($column, $desc) {
            $v1 = is_object($v) ? (isset($v->{$column}) ? $v->{$column} : null) : (isset($v[$column]) ? $v[$column] : null);
            $v2 = is_object($v) ? (isset($v->{$column}) ? $v->{$column} : null) : (isset($v[$column]) ? $v[$column] : null);
            return $desc ? $v2 <=> $v1 : $v1 <=> $v2;
        });
        return $this;
    }
    public function limit(int $limit, int $offset = 0) : Repository
    {
        $this->collection = $this->collection->rest($offset)->first($limit);
        return $this;
    }
    public function count() : int
    {
        return $this->collection->count();
    }
    public function reset() : Repository
    {
        $this->collection = $this->original;
        return $this;
    }
    public function current()
    {
        if(!($data = $this->collection->current())) {
            return null;
        }
        $this->consumed = true;
        return $data;
    }
    public function offsetGet($offset)
    {
        if(!($data = $this->collection->offsetGet($offset))) {
            return null;
        }
        $this->consumed = true;
        return $data;
    }

    public function append($entity) : Repository
    {
        $this->modified = true;
        $this->collection[] = $entity;
        return $this;
    }
    public function change($entity) : Repository
    {
        $this->modified = true;
        return $this;
    }
    public function remove($entity) : Repository
    {
        $k = $this->collection->indexOf($entity);
        if ($k !== false) {
            $this->modified = true;
            unset($this->collection[$k]);
        }
        return $this;
    }

    public function key()
    {
        return $this->collection->key();
    }
    public function rewind()
    {
        return $this->collection->rewind();
    }
    public function next()
    {
        return $this->collection->next();
    }
    public function valid()
    {
        return $this->collection->valid();
    }
    public function offsetExists($offset)
    {
        return $this->collection->offsetExists($offset);
    }
    public function offsetUnset($offset)
    {
        $this->collection->offsetUnset($offset);
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
        return [];
    }

    public function search(string $q) : SearchableRepository
    {
        $this->collection = $this->collection->filter($this->search);
        return $this;
    }
}
