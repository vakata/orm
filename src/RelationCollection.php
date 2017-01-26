<?php
namespace vakata\orm;

use \vakata\database\DatabaseInterface;
use \vakata\database\TableQuery;
use \vakata\database\TableQueryIterator;

class RelationCollection implements \Iterator, \ArrayAccess, \Countable
{
    /**
     * @var Manager
     */
    protected $manager;
    /**
     * @var string
     */
    protected $table;
    /**
     * @var \Iterator
     */
    protected $iterator;
    /**
     * @var array
     */
    protected $hydrated = [];

    public function __construct(Manager $manager, string $table, $data)
    {
        $this->table = $table;
        $this->manager = $manager;
        $this->data = $data;
        $this->iterator = null;
        // $data instanceof \Iterator ? $data : (new \ArrayObject($data))->getIterator();
    }
    protected function iterator()
    {
        if (!$this->iterator) {
            if ($this->data instanceof TableQuery) {
                $this->iterator = $this->data->iterator();
            } else if ($this->data instanceof \Iterator) {
                $this->iterator = $this->data;
            } else {
                $this->iterator = (new \ArrayObject($this->data))->getIterator();
            }
        }
        return $this->iterator;
    }
    public function hydrate()
    {
        $this->iterator();
        if ($this->iterator instanceof TableQueryIterator) {
            $tmp = [];
            $pos = $this->iterator->key();
            foreach ($this->iterator as $v) {
                $tmp[] = $v;
            }
            $this->data = $tmp;
            $this->iterator = (new \ArrayObject($tmp))->getIterator();
            if ($this->iterator->offsetExists($pos)) {
                $this->iterator->seek($pos);
            }
        }
        return $this;
    }

    public function key()
    {
        return $this->iterator()->key();
    }
    public function current()
    {
        if(!($data = $this->iterator()->current())) {
            return null;
        }
        return $data instanceof \StdClass ? $data : $this->manager->instance($this->table, $data);
    }
    public function rewind()
    {
        return $this->iterator()->rewind();
    }
    public function next()
    {
        return $this->iterator()->next();
    }
    public function valid()
    {
        return $this->iterator()->valid();
    }
    public function offsetGet($offset)
    {
        if(!($data = $this->iterator()->offsetGet($offset))) {
            return null;
        }
        return $data instanceof \StdClass ? $data : $this->manager->instance($this->table, $data);
    }
    public function offsetExists($offset)
    {
        return $this->iterator()->offsetExists($offset);
    }
    public function offsetUnset($offset)
    {
        $this->hydrate();
        return $this->iterator()->offsetUnset($offset);
    }
    public function offsetSet($offset, $value)
    {
        $this->hydrate();
        return $this->iterator()->offsetSet($offset, $value);
    }
    public function count()
    {
        // $this->hydrate();
        return count($this->data); // $this->iterator()->count();
    }
}