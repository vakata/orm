<?php
namespace vakata\orm;

use vakata\database\Result;

/**
 * A database query class
 */
class QueryIterator implements \Iterator, \ArrayAccess, \Countable
{
    protected $query;
    protected $result;
    protected $definition;
    protected $relations;
    protected $primary = null;
    protected $fetched = 0;

    public function __construct(Query $query, Result $result, array $relations = [])
    {
        $this->query = $query;
        $this->result = $result;
        $this->relations = $relations;
        $this->definition = $query->getDefinition();
    }

    public function key()
    {
        return $this->fetched;
    }
    public function current()
    {
        $result = null;
        while ($this->result->valid()) {
            $row = $this->result->current();
            $pk = [];
            foreach ($this->definition->getPrimaryKey() as $field) {
                $pk[$field] = $row[$field];
            }
            $pk = json_encode($pk);
            if ($this->primary !== null && $pk !== $this->primary) {
                break;
            }
            $this->primary = $pk;
            if (!$result) {
                $result = $row;
            }
            foreach ($this->relations as $relation) {
                $temp = $this->definition->getRelation($relation);
                if (!isset($result[$relation])) {
                    $result[$relation] = $temp['many'] ? [] : null;
                }
                $fields = [];
                $exists = false;
                foreach ($temp['table']->getColumns() as $column) {
                    $fields[$column] = $row[$relation . '___' . $column];
                    if (!$exists && $row[$relation . '___' . $column] !== null) {
                        $exists = true;
                    }
                    unset($result[$relation . '___' . $column]);
                }
                if ($exists) {
                    if ($temp['many']) {
                        $rpk = [];
                        foreach ($temp['table']->getPrimaryKey() as $field) {
                            $rpk[$field] = $fields[$field];
                        }
                        $result[$relation][json_encode($rpk)] = $fields;
                    } else {
                        $result[$relation] = $fields;
                    }
                }
            }
            $this->result->next();
        }

        if ($result) {
            foreach ($this->relations as $relation) {
                $temp = $this->definition->getRelation($relation);
                if ($temp['many']) {
                    $result[$relation] = array_values($result[$relation]);
                }
            }
        }
        return $result;
    }

    public function rewind()
    {
        $this->fetched = 0;
        $this->primary = null;
        return $this->result->rewind();
    }
    public function next()
    {
        if ($this->primary === null) {
            $this->result->next();
            if ($this->result->valid()) {
                $row = $this->result->current();
                foreach ($this->definition->getPrimaryKey() as $field) {
                    $this->primary[$field] = $row[$field];
                }
                $this->primary = json_encode($this->primary);
                return;
            }
        }
        $this->fetched ++;
        while ($this->result->valid()) {
            $row = $this->result->current();
            $pk = [];
            foreach ($this->definition->getPrimaryKey() as $field) {
                $pk[$field] = $row[$field];
            }
            $pk = json_encode($pk);
            if ($this->primary !== $pk) {
                $this->primary = $pk;
                break;
            }
            $this->result->next();
        }
    }
    public function valid()
    {
        return $this->result->valid();
    }

    public function offsetGet($offset)
    {
        $index = $this->fetched;
        $item = null;
        foreach ($this as $k => $v) {
            if ($k === $offset) {
                $item = $v;
            }
        }
        foreach ($this as $k => $v) {
            if ($k === $index) {
                break;
            }
        }
        return $item;
    }
    public function offsetExists($offset)
    {
        return $offset < $this->count();
    }
    public function count()
    {
        return $this->query->count();
    }
    public function offsetSet($offset, $value)
    {
        throw new ORMException('Invalid call to offsetSet');
    }
    public function offsetUnset($offset)
    {
        throw new ORMException('Invalid call to offsetUnset');
    }
}
