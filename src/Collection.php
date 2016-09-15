<?php
namespace vakata\orm;

/**
 * A collection class - created automatically by the manager.
 */
class Collection implements \Iterator, \ArrayAccess, \Countable
{
    protected $class;
    protected $query;
    protected $single;
    protected $manager;
    protected $current;

    /**
     * Create a collection instance
     * @method __construct
     * @param  Query        $query   a query to populate this collection with
     * @param  Manager      $manager the manager to which this collection belongs
     * @param  string       $class   the class name to use when creating items
     * @param  array|null   $current optional prepopulated query result
     * @param  bool|boolean $single  optional flag indicating if this collection should only contain a single element
     */
    public function __construct(
        Query $query,
        Manager $manager,
        string $class,
        array $current = null,
        bool $single = false
    ) {
        $this->class = $class;
        $this->query = $query;
        $this->single = $single;
        $this->manager = $manager;
        $this->current = $current;
    }
    /**
     * Get the count of items in the collection
     * @method count
     * @return int the number of items in the collection
     */
    public function count() : int
    {
        return $this->query->count();
    }
    /**
     * Reset the collection - useful to remove applied filters, orders, etc.
     * @method reset
     * @return self
     */
    public function reset() : TableInterface
    {
        $this->query->reset();
        $this->current = null;
        return $this;
    }
    /**
     * Make sure the collection will also contain some related data without requiring a new query
     * @method with
     * @param  string $relation [description]
     * @return self
     */
    public function with(string $relation) : Collection
    {
        $this->query->with($relation);
        $this->current = null;
        return $this;
    }
    /**
     * Filter a collection by a column and a value
     * @method filter
     * @param  string $column the column to filter by
     * @param  mixed  $value  the required value of the column
     * @return self
     */
    public function filter(string $column, $value) : Collection
    {
        $this->query->filter($column, $value);
        $this->current = null;
        return $this;
    }
    /**
     * Sort by a column
     * @method sort
     * @param  string       $column the column name to sort by
     * @param  bool|boolean $desc   should the sort be in descending order, defaults to `false`
     * @return self
     */
    public function sort(string $column, bool $desc = false) : Collection
    {
        $this->query->sort($column, $desc);
        $this->current = null;
        return $this;
    }
    /**
     * Get a part of the data
     * @method paginate
     * @param  int|integer $page    the page number to get (1-based), defaults to 1
     * @param  int|integer $perPage the number of records per page - defaults to 25
     * @return self
     */
    public function paginate(int $page = 1, int $perPage = 25) : Collection
    {
        $this->query->paginate($page, $perPage);
        $this->current = null;
        return $this;
    }
    /**
     * Apply an advanced filter on the collection (can be called multiple times)
     * @method where
     * @param  string $sql    SQL statement to be used in the where clause
     * @param  array  $params parameters for the SQL statement (defaults to an empty array)
     * @return self
     */
    public function where(string $sql, array $params = []) : Collection
    {
        $this->query->where($sql, $params);
        $this->current = null;
        return $this;
    }
    /**
     * Apply advanced sorting to the collection
     * @method order
     * @param  string $sql    SQL statement to use in the ORDER clause
     * @param  array  $params optional params for the statement (defaults to an empty array)
     * @return self
     */
    public function order(string $sql, array $params = []) : Collection
    {
        $this->query->order($sql, $params);
        $this->current = null;
        return $this;
    }
    /**
     * Apply an advanced limit
     * @method limit
     * @param  int         $limit  number of rows to return
     * @param  int|integer $offset number of rows to skip from the beginning
     * @return self
     */
    public function limit(int $limit, int $offset = 0) : Collection
    {
        $this->query->limit($limit, $offset);
        $this->current = null;
        return $this;
    }

    /**
     * Get the whole object either as an array (if `single` is `false`) or the single resulting object
     * @method get
     * @return mixed 
     */
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
    /**
     * Find an instance within the collection using the instance's primary key
     * @method find
     * @param  mixed  $key the instance's primary key
     * @return mixed  the entity or `null` if not found in collection
     */
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
            return $this->current[$offset] = $this->manager->entity(
                $this->class,
                $item,
                $item,
                $this->query->getDefinition()
            );
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
            return $this->current[$this->key()] = $this->manager->entity(
                $this->class,
                $item,
                $item,
                $this->query->getDefinition()
            );
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
