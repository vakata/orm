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
    protected $li_of = [0,0];

    protected $withr = [];

    /**
     * Create an instance
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
     * @return Table        the definition
     */
    public function getDefinition() : Table
    {
        return $this->definition;
    }

    protected function getColumn($column)
    {
        $column = explode('.', $column, 2);
        if (count($column) === 1) {
            $column = [ $this->definition->getName(), $column[0] ];
        }
        if ($column[0] === $this->definition->getName()) {
            $col = $this->definition->getColumn($column[1]);
            if (!$col) {
                throw new ORMException('Invalid column name in own table');
            }
        } else {
            if (!$this->definition->hasRelation($column[0])) {
                throw new ORMException('Invalid relation name');
            }
            $col = $this->definition->getRelation($column[0])['table']->getColumn($column[1]);
            if (!$col) {
                throw new ORMException('Invalid column name in related table');
            }
        }
        return [ 'name' => implode('.', $column), 'data' => $col ];
    }
    protected function normalizeValue(Column $col, $value)
    {
        switch ($col->getBasicType()) {
            case 'date':
                if (is_string($value)) {
                    return date('Y-m-d', strtotime($value));
                }
                if (is_int($value)) {
                    return date('Y-m-d', $value);
                }
                if ($value instanceof \DateTime) {
                    return $value->format('Y-m-d');
                }
                return $value;
            case 'datetime':
                if (is_string($value)) {
                    return date('Y-m-d H:i:s', strtotime($value));
                }
                if (is_int($value)) {
                    return date('Y-m-d H:i:s', $value);
                }
                if ($value instanceof \DateTime) {
                    return $value->format('Y-m-d H:i:s');
                }
                return $value;
            case 'enum':
                if (is_int($value)) {
                    return $value;
                }
                if (!in_array($value, $col->getValues())) {
                    return 0;
                }
                return $value;
            case 'int':
                return (int)$value;
            default:
                return $value;
        }
    }
    /**
     * Filter the results by a column and a value
     * @param  string $column the column name to filter by (related columns can be used - for example: author.name)
     * @param  mixed  $value  a required value, array of values or range of values (range example: ['beg'=>1,'end'=>3])
     * @return self
     */
    public function filter(string $column, $value) : Query
    {
        list($name, $column) = array_values($this->getColumn($column));
        if (is_null($value)) {
            return $this->where($name . ' IS NULL');
        }
        if (!is_array($value)) {
            return $this->where(
                $name . ' = ?',
                [ $this->normalizeValue($column, $value) ]
            );
        }
        if (isset($value['beg']) && isset($value['end'])) {
            return $this->where(
                $name.' BETWEEN ? AND ?',
                [
                    $this->normalizeValue($column, $value['beg']),
                    $this->normalizeValue($column, $value['end'])
                ]
            );
        }
        return $this->where(
            $name . ' IN (??)',
            [ array_map(function ($v) use ($column) { return $this->normalizeValue($column, $v); }, $value) ]
        );
    }
    /**
     * Sort by a column
     * @param  string       $column the column name to sort by (related columns can be used - for example: author.name)
     * @param  bool|boolean $desc   should the sorting be in descending order, defaults to `false`
     * @return self
     */
    public function sort(string $column, bool $desc = false) : Query
    {
        return $this->order($this->getColumn($column)['name'] . ' ' . ($desc ? 'DESC' : 'ASC'));
    }
    /**
     * Get a part of the data
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
     * @return self
     */
    public function reset() : Query
    {
        $this->where = [];
        $this->order = [];
        $this->li_of = [0,0];
        return $this;
    }
    /**
     * Apply an advanced filter (can be called multiple times)
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
     * @param  int         $limit  number of rows to return
     * @param  int         $offset number of rows to skip from the beginning (defaults to 0)
     * @return self
     */
    public function limit(int $limit, int $offset = 0) : Query
    {
        $this->li_of = [ $limit, $offset ];
        return $this;
    }
    /**
     * Get the number of records
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
            $fields[$k] = $this->getColumn($v)['name'];
        }
        foreach ($primary as $field) {
            $field = $this->getColumn($field)['name'];
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
            $porder[] = $this->getColumn($field)['name'];
        }
        $sql .= (count($this->order) ? ', ' : 'ORDER BY ') . implode(', ', $porder) . ' ';

        if ($this->li_of[0]) {
            if ($this->db->driver() === 'oracle') {
                if ((int)($this->db->settings()->options['version'] ?? 0) >= 12) {
                    $sql .= 'OFFSET ' . $this->li_of[1] . ' ROWS FETCH NEXT ' . $this->li_of[0] . ' ROWS ONLY';
                } else {
                    $fields = array_map(function ($v) {
                        $v = explode(' ', trim($v), 2);
                        if (count($v) === 2) { return $v[1]; }
                        $v = explode('.', $v[0], 2);
                        return count($v) === 2 ? $v[1] : $v[0];
                    }, $select);
                    $sql = "SELECT " . implode(', ', $fields) . " 
                            FROM (
                                SELECT tbl__.*, rownum rnum__ FROM (
                                    " . $sql . "
                                ) tbl__ 
                                WHERE rownum <= " . ($this->li_of[0] + $this->li_of[1]) . "
                            ) WHERE rnum__ > " . $this->li_of[1];
                }
            } else {
                $sql .= 'LIMIT ' . $this->li_of[0] . ' OFFSET ' . $this->li_of[1];
            }
        }
        return new QueryIterator($this, $this->db->get($sql, $par), $this->withr);
    }
    /**
     * Perform the actual fetch
     * @param  array|null $fields optional array of columns to select (related columns can be used too)
     * @return array               the query result as an array
     */
    public function select(array $fields = null) : array
    {
        return iterator_to_array($this->iterator($fields));
    }
    /**
     * Insert a new row in the table
     * @param  array   $data   key value pairs, where each key is the column name and the value is the value to insert
     * @return array           the inserted ID where keys are column names and values are column values
     */
    public function insert(array $data) : array
    {
        $table = $this->definition->getName();
        $columns = $this->definition->getFullColumns();
        $insert = [];
        foreach ($data as $column => $value) {
            if (isset($columns[$column])) {
                $insert[$column] = $this->normalizeValue($columns[$column], $value);
            }
        }
        if (!count($insert)) {
            throw new ORMException('No valid columns to insert');
        }
        $sql = 'INSERT INTO '.$table.' ('.implode(', ', array_keys($insert)).') VALUES (??)';
        $par = [$insert];
        //if ($update) {
        //    $sql .= 'ON DUPLICATE KEY UPDATE ';
        //    $sql .= implode(', ', array_map(function ($v) { return $v . ' = ?'; }, array_keys($insert))) . ' ';
        //    $par  = array_merge($par, $insert);
        //}
        if ($this->db->driver() === 'oracle') {
            $primary = $this->definition->getPrimaryKey();
            $ret = [];
            foreach ($primary as $k) {
                $ret[$k] = str_repeat(' ', 255);
                $par[] = &$ret[$k];
            }
            $sql .= ' RETURNING ' . implode(',', $primary) . ' INTO ' . implode(',', array_fill(0, count($primary), '?'));
            $this->db->query($sql, $par);
            return $ret;
        } else {
            $ret = [];
            $ins = $this->db->query($sql, $par)->insertId();
            foreach ($this->definition->getPrimaryKey() as $k) {
                $ret[$k] = $data[$k] ?? $ins;
            }
            return $ret;
        }
    }
    /**
     * Update the filtered rows with new data
     * @param  array  $data key value pairs, where each key is the column name and the value is the value to insert
     * @return int          the number of affected rows
     */
    public function update(array $data) : int
    {
        $table = $this->definition->getName();
        $columns = $this->definition->getFullColumns();
        $update = [];
        foreach ($data as $column => $value) {
            if (isset($columns[$column])) {
                $update[$column] = $this->normalizeValue($columns[$column], $value);
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
        return $this->db->query($sql, $par)->affected();
    }
    /**
     * Delete the filtered rows from the DB
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
        return $this->db->query($sql, $par)->affected();
    }
    /**
     * Solve the n+1 queries problem by prefetching a relation by name
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
