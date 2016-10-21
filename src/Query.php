<?php
namespace vakata\orm;

use vakata\database\DatabaseInterface;

/**
 * A database query class
 */
class Query
{
    protected $db;
    protected $definition;

    protected $where = [];
    protected $order = [];
    protected $limit = '';

    protected $withr = [];

    /**
     * Create a query by table name
     * @method fromDatabase
     * @param  DatabaseInterface $db    the database instance
     * @param  string            $table the table name to query
     * @return Query                    the query object
     */
    public static function fromDatabase(DatabaseInterface $db, string $table)
    {
        return new static($db, Table::fromDatabase($db, $table));
    }
    /**
     * Create an instance
     * @method __construct
     * @param  DatabaseInterface $db         the database instance
     * @param  Table   $definition the table definition of the table to query
     */
    public function __construct(DatabaseInterface $db, Table $definition)
    {
        $this->db = $db;
        $this->definition = $definition;
    }
    public function __clone()
    {
        $this->reset();
    }
    /**
     * Get the table definition of the queried table
     * @method getDefinition
     * @return Table        the definition
     */
    public function getDefinition() : Table
    {
        return $this->definition;
    }

    protected function normalizeColumn($column)
    {
        $column = explode('.', $column, 2);
        if (count($column) === 1) {
            $column = [ $this->definition->getName(), $column[0] ];
        }
        if ($column[0] === $this->definition->getName()) {
            if (!in_array($column[1], $this->definition->getColumns())) {
                throw new ORMException('Invalid column name in own table');
            }
            return implode('.', $column);
        }
        if (!$this->definition->hasRelation($column[0])) {
            throw new ORMException('Invalid relation name');
        }
        if (!in_array($column[1], $this->definition->getRelation($column[0])['table']->getColumns())) {
            throw new ORMException('Invalid column name in related table');
        }
        return implode('.', $column);
    }
    /**
     * Filter the results by a column and a value
     * @method filter
     * @param  string $column the column name to filter by (related columns can be used - for example: author.name)
     * @param  mixed  $value  a required value, array of values or range of values (range example: ['beg'=>1,'end'=>3])
     * @return self
     */
    public function filter(string $column, $value) : Query
    {
        $column = $this->normalizeColumn($column);
        if (is_null($value)) {
            return $this->where($column . ' IS NULL');
        }
        if (!is_array($value)) {
            return $this->where($column . ' = ?', [ $value ]);
        }
        if (isset($value['beg']) && isset($value['end'])) {
            return $this->where($column.' BETWEEN ? AND ?', [$value['beg'], $value['end']]);
        }
        return $this->where($column . ' IN (??)', [ $value ]);
    }
    /**
     * Sort by a column
     * @method sort
     * @param  string       $column the column name to sort by (related columns can be used - for example: author.name)
     * @param  bool|boolean $desc   should the sorting be in descending order, defaults to `false`
     * @return self
     */
    public function sort(string $column, bool $desc = false) : Query
    {
        $column = $this->normalizeColumn($column);
        return $this->order($column . ' ' . ($desc ? 'DESC' : 'ASC'));
    }
    /**
     * Get a part of the data
     * @method paginate
     * @param  int|integer $page    the page number to get (1-based), defaults to 1
     * @param  int|integer $perPage the number of records per page - defaults to 25
     * @return self
     */
    public function paginate(int $page = 1, int $perPage = 25) : Query
    {
        return $this->limit($perPage, ($page - 1) * $perPage);
    }
    public function __call($name, $data)
    {
        if (strpos($name, 'filterBy') === 0) {
            return $this->filter(strtolower(substr($name, 8)), $data[0]);
        }
        if (strpos($name, 'sortBy') === 0) {
            return $this->filter(strtolower(substr($name, 6)), $data[0]);
        }
    }
    /**
     * Remove all filters, sorting, etc
     * @method reset
     * @return self
     */
    public function reset() : Query
    {
        $this->where = [];
        $this->order = [];
        $this->limit = '';
        return $this;
    }
    /**
     * Apply an advanced filter (can be called multiple times)
     * @method where
     * @param  string $sql    SQL statement to be used in the where clause
     * @param  array  $params parameters for the SQL statement (defaults to an empty array)
     * @return self
     */
    public function where(string $sql, array $params = []) : Query
    {
        $this->where[] = [ $sql, $params ];
        return $this;
    }
    /**
     * Apply advanced sorting
     * @method order
     * @param  string $sql    SQL statement to use in the ORDER clause
     * @param  array  $params optional params for the statement (defaults to an empty array)
     * @return self
     */
    public function order(string $sql, array $params = []) : Query
    {
        $this->order = [ $sql, $params ];
        return $this;
    }
    /**
     * Apply an advanced limit
     * @method limit
     * @param  int         $limit  number of rows to return
     * @param  int|integer $offset number of rows to skip from the beginning
     * @return self
     */
    public function limit(int $limit, int $offset = 0) : Query
    {
        $this->limit = 'LIMIT ' . $limit . ' OFFSET ' . $offset;
        return $this;
    }
    /**
     * Get the number of records
     * @method count
     * @return int the total number of records (does not respect pagination)
     */
    public function count() : int
    {
        $table = $this->definition->getName();
        $primary = $this->definition->getPrimaryKey();
        $sql = 'SELECT COUNT(DISTINCT '.$table.'.'.implode(', '.$table.'.', $primary).') FROM '.$table.' ';
        $par = [];
        foreach ($this->definition->getRelations() as $k => $v) {
            if ($v['pivot']) {
                $sql .= 'LEFT JOIN '.$v['pivot']->getName().' '.$k.'_pivot ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = $table.'.'.$kk.' = '.$k.'_pivot.'.$vv.' ';
                }
                $sql .= implode(' AND ', $tmp);
                $sql .= 'LEFT JOIN '.$v['table']->getName().' '.$k.' ON ';
                $tmp = [];
                foreach ($v['pivot_keymap'] as $kk => $vv) {
                    $tmp[] = $k.'.'.$vv.' = '.$k.'_pivot.'.$kk.' ';
                }
                $sql .= implode(' AND ', $tmp);
            } else {
                $sql .= 'LEFT JOIN '.$v['table']->getName().' '.$k.' ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = $table.'.'.$kk.' = '.$k.'.'.$vv.' ';
                }
                if ($v['sql']) {
                    $tmp[] = $v['sql'] . ' ';
                    $par = array_merge($par, $v['par']);
                }
                $sql .= implode(' AND ', $tmp);
            }
        }
        if (count($this->where)) {
            $sql .= 'WHERE ';
            $tmp = [];
            foreach ($this->where as $v) {
                $tmp[] = '(' . $v[0] . ')';
                $par = array_merge($par, $v[1]);
            }
            $sql .= implode(' AND ', $tmp).' ';
        }
        return $this->db->one($sql, $par);
    }
    /**
     * Perform the actual fetch
     * @method iterator
     * @param  array|null $fields optional array of columns to select (related columns can be used too)
     * @return QueryIterator               the query result as an iterator
     */
    public function iterator(array $fields = null) : QueryIterator
    {
        $table = $this->definition->getName();
        $primary = $this->definition->getPrimaryKey();
        if ($fields === null) {
            $fields = $this->definition->getColumns();
        }
        foreach ($fields as $k => $v) {
            $fields[$k] = $this->normalizeColumn($v);
        }
        foreach ($primary as $field) {
            $field = $this->normalizeColumn($field);
            if (!in_array($field, $fields)) {
                $fields[] = $field;
            }
        }
        $relations = $this->withr;
        foreach ($this->definition->getRelations() as $k => $v) {
            foreach ($fields as $field) {
                if (strpos($field, $k . '.') === 0) {
                    $relations[] = $k;
                }
            }
            foreach ($this->where as $v) {
                if (strpos($v[0], $k . '.') !== false) {
                    $relations[] = $k;
                }
            }
            if (isset($this->order[0]) && strpos($this->order[0], $k . '.') !== false) {
                $relations[] = $k;
            }
        }
        $select = [];
        foreach ($fields as $k => $field) {
            $select[] = $field . (!is_numeric($k) ? ' ' . $k : '');
        }
        foreach ($this->withr as $relation) {
            foreach ($this->definition->getRelation($relation)['table']->getColumns() as $column) {
                $select[] = $relation . '.' . $column . ' ' . $relation . '___' . $column;
            }
        }
        $sql = 'SELECT '.implode(', ', $select).' FROM '.$table.' ';
        $par = [];
        foreach (array_unique($relations) as $relation) {
            $v = $this->definition->getRelation($relation);
            if ($v['pivot']) {
                $sql .= 'LEFT JOIN '.$v['pivot']->getName().' '.$relation.'_pivot ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = $table.'.'.$kk.' = '.$relation.'_pivot.'.$vv.' ';
                }
                $sql .= implode(' AND ', $tmp);
                $sql .= 'LEFT JOIN '.$v['table']->getName().' '.$relation.' ON ';
                $tmp = [];
                foreach ($v['pivot_keymap'] as $kk => $vv) {
                    $tmp[] = $relation.'.'.$vv.' = '.$relation.'_pivot.'.$kk.' ';
                }
                $sql .= implode(' AND ', $tmp);
            } else {
                $sql .= 'LEFT JOIN '.$v['table']->getName().' '.$relation.' ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = $table.'.'.$kk.' = '.$relation.'.'.$vv.' ';
                }
                if ($v['sql']) {
                    $tmp[] = $v['sql'] . ' ';
                    $par = array_merge($par, $v['par']);
                }
                $sql .= implode(' AND ', $tmp);
            }
        }
        if (count($this->where)) {
            $sql .= 'WHERE ';
            $tmp = [];
            foreach ($this->where as $v) {
                $tmp[] = '(' . $v[0] . ')';
                $par = array_merge($par, $v[1]);
            }
            $sql .= implode(' AND ', $tmp).' ';
        }
        //if ($this->definition->hasRelations()) {
        //    $sql .= 'GROUP BY '.$table.'.'.implode(', '.$table.'.', $primary).' ';
        //}
        if (count($this->order)) {
            $sql .= 'ORDER BY ' . $this->order[0];
            $par = array_merge($par, $this->order[1]);
        }
        $porder = [];
        foreach ($primary as $field) {
            $porder[] = $this->normalizeColumn($field);
        }
        $sql .= (count($this->order) ? ', ' : 'ORDER BY ') . implode(', ', $porder);

        if ($this->limit) {
            $sql .= $this->limit;
        }
        return new QueryIterator($this, $this->db->get($sql, $par), $this->withr);
    }
    /**
     * Perform the actual fetch
     * @method select
     * @param  array|null $fields optional array of columns to select (related columns can be used too)
     * @return array               the query result as an array
     */
    public function select(array $fields = null) : array
    {
        return iterator_to_array($this->iterator($fields));
    }
    /**
     * Insert a new row in the table
     * @method insert
     * @param  array   $data   key value pairs, where each key is the column name and the value is the value to insert
     * @param  boolean $update if the PK exists should the row be updated with the new data, defaults to `false`
     * @return mixed           the last insert ID from the database
     */
    public function insert(array $data, $update = false)
    {
        $table = $this->definition->getName();
        $columns = $this->definition->getColumns();
        $insert = [];
        foreach ($data as $column => $value) {
            if (in_array($column, $columns)) {
                $insert[$column] = $value;
            }
        }
        if (!count($insert)) {
            throw new ORMException('No valid columns to insert');
        }
        $sql = 'INSERT INTO '.$table.' ('.implode(', ', array_keys($insert)).') VALUES (??)';
        $par = [$insert];
        if ($update) {
            $sql .= 'ON DUPLICATE KEY UPDATE ';
            $sql .= implode(', ', array_map(function ($v) { return $v . ' = ?'; }, array_keys($insert))) . ' ';
            $par  = array_merge($par, $insert);
        }
        return $this->db->query($sql, $par)->insertId();
    }
    /**
     * Update the filtered rows with new data
     * @method update
     * @param  array  $data key value pairs, where each key is the column name and the value is the value to insert
     * @return int          the number of affected rows
     */
    public function update(array $data) : int
    {
        $table = $this->definition->getName();
        $columns = $this->definition->getColumns();
        $update = [];
        foreach ($data as $column => $value) {
            if (in_array($column, $columns)) {
                $update[$column] = $value;
            }
        }
        if (!count($update)) {
            throw new ORMException('No valid columns to update');
        }
        $sql = 'UPDATE '.$table.' SET ';
        $par = [];
        $sql .= implode(', ', array_map(function ($v) { return $v . ' = ?'; }, array_keys($update))) . ' ';
        $par = array_merge($par, array_values($update));
        if (count($this->where)) {
            $sql .= 'WHERE ';
            $tmp = [];
            foreach ($this->where as $v) {
                $tmp[] = $v[0];
                $par = array_merge($par, $v[1]);
            }
            $sql .= implode(' AND ', $tmp) . ' ';
        }
        if (count($this->order)) {
            $sql .= $this->order[0];
            $par = array_merge($par, $this->order[1]);
        }
        if ($this->limit) {
            $sql .= $this->limit;
        }
        return $this->db->query($sql, $par)->affected();
    }
    /**
     * Delete the filtered rows from the DB
     * @method delete
     * @return int the number of deleted rows
     */
    public function delete() : int
    {
        $table = $this->definition->getName();
        $sql = 'DELETE FROM '.$table.' ';
        $par = [];
        if (count($this->where)) {
            $sql .= 'WHERE ';
            $tmp = [];
            foreach ($this->where as $v) {
                $tmp[] = $v[0];
                $par = array_merge($par, $v[1]);
            }
            $sql .= implode(' AND ', $tmp) . ' ';
        }
        if (count($this->order)) {
            $sql .= $this->order[0];
            $par = array_merge($par, $this->order[1]);
        }
        if ($this->limit) {
            $sql .= $this->limit;
        }
        return $this->db->query($sql, $par)->affected();
    }
    /**
     * Solve the n+1 queries problem by prefetching a relation by name
     * @method with
     * @param  string $relation the relation name to fetch along with the data
     * @return self
     */
    public function with(string $relation) : Query
    {
        if (!$this->definition->hasRelation($relation)) {
            throw new ORMException('Invalid relation name');
        }
        $this->withr[$relation] = $relation;
        return $this;
    }
}
