<?php

namespace vakata\orm;

use vakata\database\DatabaseInterface;

/**
 * A table abstraction with support for filtering, ordering, create, update, delete and array-like access and iteration.
 */
class Table implements TableInterface
{
    protected $database = null;
    protected $table = null;
    protected $definition = null;
    protected $relations = [];

    protected $ext = [];
    protected $new = [];
    protected $del = [];

    protected $result = null;
    protected $filter = [];
    protected $params = [];
    protected $order = null;

    /**
     * Create an instance.
     * @method __construct
     * @param  DatabaseInterface             $database   database instance
     * @param  string                        $table      the table name
     * @param  \vakata\orm\TableDefinitionInterface|null $definition the optional table defintion
     */
    public function __construct(DatabaseInterface $database, $table, TableDefinitionInterface $definition = null)
    {
        $this->database = $database;
        $this->table = $table;
        $this->definition = isset($definition) ? $definition : new TableDefinition($this->database, $this->table);
    }
    public function __clone()
    {
        $this->reset();
    }
    /**
     * Returns the database instance being used.
     * @method getDatabase
     * @return \vakata\database\DatabaseInterface      the database instance
     */
    public function getDatabase()
    {
        return $this->database;
    }
    /**
     * Get the table definition.
     * @method getDefinition
     * @return \vakata\orm\TableDefinitionInterface        the table definition
     */
    public function getDefinition()
    {
        return $this->definition;
    }
    /**
     * Get the defined relationships to other tables.
     * @method getRelations
     * @return array       the defined relationships
     */
    public function &getRelations()
    {
        return $this->relations;
    }
    /**
     * Get only the names of the defined relationships to other tables.
     * @method getRelationKeys
     * @return array          an array of strings
     */
    public function getRelationKeys()
    {
        return array_keys($this->relations);
    }

    // relations
    protected function getRelatedTable($toTable)
    {
        if (!($toTable instanceof self)) {
            if (!is_array($toTable)) {
                $toTable = ['table' => $toTable];
            }
            if (!isset($toTable['definition'])) {
                $toTable['definition'] = null;
            }
            if (is_array($toTable['definition'])) {
                $toTable['definition'] = new TableDefinitionArray($toTable['table'], $toTable['definition']);
            }
            $toTable = new self($this->getDatabase(), $toTable['table'], $toTable['definition']);
        }

        return $toTable;
    }
    public function addAdvancedRelation($toTable, $name, array $keymap, $many = true, $pivot = null, array $map = [], $sql = null, array $par = [])
    {
        if (!count($keymap)) {
            throw new ORMException('No linking fields specified');
        }
        $toTable = $this->getRelatedTable($toTable);
        $this->relations[$name] = [
            'name' => $name,
            'table' => $toTable,
            'keymap' => $keymap,
            'many' => (bool) $many,
            'pivot' => $pivot,
            'pivot_keymap' => $map,
            'sql' => $sql,
            'par' => $par
        ];

        return $this;
    }
    /**
     * Define a relationship where a remote table has 0 or 1 rows for each row in the current table.
     * The remote table generally should have a field (or fields) pointing to the ID of the current table.
     * @method hasOne
     * @param  \vakata\orm\TableDefintionInterface|string  $toTable       the table definition or name
     * @param  string        $name          the name of the relationship (the name is used to access the data later)
     * @param  string|array  $toTableColumn the column (or columns) in the foreign table
     * @return self
     */
    public function hasOne($toTable, $name = null, $toTableColumn = null, $sql = null, array $par = [])
    {
        $toTable = $this->getRelatedTable($toTable);
        $columns = $toTable->definition->getColumns();

        $keymap = [];
        if (!isset($toTableColumn)) {
            $toTableColumn = [];
        }
        if (!is_array($toTableColumn)) {
            $toTableColumn = [$toTableColumn];
        }
        foreach ($this->definition->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($toTableColumn[$pkField])) {
                $key = $toTableColumn[$pkField];
            } elseif (isset($toTableColumn[$k])) {
                $key = $toTableColumn[$k];
            } else {
                $key = $this->definition->getName().'_'.$pkField;
            }
            if (!in_array($key, $columns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $keymap[$pkField] = $key;
        }

        if (!isset($name)) {
            $name = $toTable->definition->getName().'_'.implode('_', array_keys($keymap));
        }
        $this->relations[$name] = [
            'name' => $name,
            'table' => $toTable,
            'keymap' => $keymap,
            'many' => false,
            'pivot' => null,
            'pivot_keymap' => [],
            'sql' => $sql,
            'par' => $par
        ];

        return $this;
    }
    /**
     * Define a relationship where a remote table has any number of rows for each row in the current table.
     * The remote table generally should have a field (or fields) pointing to the ID of the current table.
     * @method hasMany
     * @param  \vakata\orm\TableDefintionInterface|string  $toTable       the table definition or name
     * @param  string        $name          the name of the relationship (the name is used to access the data later)
     * @param  string|array  $toTableColumn the column (or columns) in the foreign table
     * @return self
     */
    public function hasMany($toTable, $name = null, $toTableColumn = null, $sql = null, array $par = [])
    {
        $toTable = $this->getRelatedTable($toTable);
        $columns = $toTable->definition->getColumns();

        $keymap = [];
        if (!isset($toTableColumn)) {
            $toTableColumn = [];
        }
        if (!is_array($toTableColumn)) {
            $toTableColumn = [$toTableColumn];
        }
        foreach ($this->definition->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($toTableColumn[$pkField])) {
                $key = $toTableColumn[$pkField];
            } elseif (isset($toTableColumn[$k])) {
                $key = $toTableColumn[$k];
            } else {
                $key = $this->definition->getName().'_'.$pkField;
            }
            if (!in_array($key, $columns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $keymap[$pkField] = $key;
        }

        if (!isset($name)) {
            $name = $toTable->definition->getName().'_'.implode('_', array_keys($keymap));
        }
        $this->relations[$name] = [
            'name' => $name,
            'table' => $toTable,
            'keymap' => $keymap,
            'many' => true,
            'pivot' => null,
            'pivot_keymap' => [],
            'sql' => $sql,
            'par' => $par
        ];

        return $this;
    }
    /**
     * Define a relationship where a remote table has 0 or 1 rows for each row in the current table.
     * The current table generally should have a field (or fields) pointing to the ID of the foreign table.
     * @method belongsTo
     * @param  \vakata\orm\TableDefintionInterface|string  $toTable       the table definition or name
     * @param  string        $name          the name of the relationship (the name is used to access the data later)
     * @param  string|array  $toTableColumn the column (or columns) in the current table
     * @return self
     */
    public function belongsTo($toTable, $name = null, $localColumn = null, $sql = null, array $par = [])
    {
        $toTable = $this->getRelatedTable($toTable);
        $columns = $this->definition->getColumns();

        $keymap = [];
        if (!isset($localColumn)) {
            $localColumn = [];
        }
        if (!is_array($localColumn)) {
            $localColumn = [$localColumn];
        }
        foreach ($toTable->definition->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($localColumn[$pkField])) {
                $key = $localColumn[$pkField];
            } elseif (isset($localColumn[$k])) {
                $key = $localColumn[$k];
            } else {
                $key = $toTable->definition->getName().'_'.$pkField;
            }
            if (!in_array($key, $columns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $keymap[$key] = $pkField;
        }

        if (!isset($name)) {
            $name = $toTable->definition->getName().'_'.implode('_', array_keys($keymap));
        }
        $this->relations[$name] = [
            'name' => $name,
            'table' => $toTable,
            'keymap' => $keymap,
            'many' => false,
            'pivot' => null,
            'pivot_keymap' => [],
            'sql' => $sql,
            'par' => $par
        ];

        return $this;
    }
    /**
     * Add a relationship which uses a pivot table (many to many).
     * @method manyToMany
     * @param  \vakata\orm\TableDefintionInterface|string  $toTable       the table definition or name
     * @param  \vakata\orm\TableDefintionInterface|string  $pivot         the pivot table
     * @param  string        $name          the name of the relationship (the name is used to access the data later)
     * @param  string|array  $toTableColumn the column (or columns) in the pivot table connecting to the current table primary key
     * @param  string|array  $rlTableColumn the column (or columns) in the pivot table connecting to the related table primary key
     * @return self
     */
    public function manyToMany($toTable, $pivot, $name = null, $toTableColumn = null, $localColumn = null)
    {
        $toTable = $this->getRelatedTable($toTable);
        $ptTable = $this->getRelatedTable($pivot);

        $pivotColumns = $ptTable->definition->getColumns();

        $keymap = [];
        if (!isset($toTableColumn)) {
            $toTableColumn = [];
        }
        if (!is_array($toTableColumn)) {
            $toTableColumn = [$toTableColumn];
        }
        foreach ($this->definition->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($toTableColumn[$pkField])) {
                $key = $toTableColumn[$pkField];
            } elseif (isset($toTableColumn[$k])) {
                $key = $toTableColumn[$k];
            } else {
                $key = $this->definition->getName().'_'.$pkField;
            }
            if (!in_array($key, $pivotColumns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $keymap[$pkField] = $key;
        }

        $pivotKeymap = [];
        if (!isset($localColumn)) {
            $localColumn = [];
        }
        if (!is_array($localColumn)) {
            $localColumn = [$localColumn];
        }
        foreach ($toTable->definition->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($localColumn[$pkField])) {
                $key = $localColumn[$pkField];
            } elseif (isset($localColumn[$k])) {
                $key = $localColumn[$k];
            } else {
                $key = $toTable->definition->getName().'_'.$pkField;
            }
            if (!in_array($key, $pivotColumns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $pivotKeymap[$key] = $pkField;
        }

        if (!isset($name)) {
            $name = $toTable->definition->getName().'_'.implode('_', array_keys($keymap));
        }
        $this->relations[$name] = [
            'name' => $name,
            'table' => $toTable,
            'keymap' => $keymap,
            'many' => true,
            'pivot' => $pivot,
            'pivot_keymap' => $pivotKeymap,
            'sql' => null,
            'par' => []
        ];

        return $this;
    }
    /**
     * Perform the actual data fetching (after filtering and / or ordering), all params are optional.
     * @method select
     * @param  integer    $limit  how many rows to fetch
     * @param  integer    $offset skip $offset many rows from the beginning
     * @param  array|null $fields an array of column names to fetch, if related columns are needed use <relationshipname>.<columnname>
     * @return self
     */
    public function select($limit = 0, $offset = 0, array $fields = null)
    {
        if ($fields && count($fields)) {
            $temp = [];
            foreach ($fields as $k => $v) {
                if (!strpos($v, '.')) {
                    if (in_array($v, $this->definition->getColumns()) || $v === '*') {
                        $temp[] = 't.'.$v;
                    }
                } else {
                    if (preg_match('(^[a-z_0-9]+\.[a-z_0-9*]+$)i', $v)) {
                        $v = explode('.', $v, 2);
                        if (isset($this->relations[$v[0]]) &&
                            ($v[1] === '*'||in_array($v[1],$this->relations[$v[0]]['table']->definition->getColumns()))
                        ) {
                            $temp[] = $v[1] === '*' ? implode('.', $v) : implode('.', $v).' AS '.implode('___', $v);
                            // add the needed local keys, so that remote keys will work
                            foreach (array_keys($this->relations[$v[0]]['keymap']) as $k) {
                                $temp[] = 't.'.$k;
                            }
                        }
                    }
                }
            }
            $fields = $temp;
        }
        if (!$fields || !count($fields)) {
            $fields = [];
            foreach ($this->definition->getColumns() as $c) {
                $fields[] = 't.'.$c;
            }
        }
        $sql = 'SELECT '.implode(', ', array_unique($fields)).' FROM '.$this->table.' AS t ';
        $par = $this->params;

        foreach ($this->relations as $k => $v) {
            if ($v['pivot']) {
                $sql .= 'LEFT JOIN '.$v['pivot'].' AS '.$k.'_pivot ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = 't.'.$kk.' = '.$k.'_pivot.'.$vv.' ';
                }
                $sql .= implode(' AND ', $tmp);

                $sql .= 'LEFT JOIN '.$v['table']->definition->getName().' AS '.$k.' ON ';
                $tmp = [];
                foreach ($v['pivot_keymap'] as $kk => $vv) {
                    $tmp[] = $k.'.'.$vv.' = '.$k.'_pivot.'.$kk.' ';
                }
                $sql .= implode(' AND ', $tmp);
            } else {
                $sql .= 'LEFT JOIN '.$v['table']->definition->getName().' AS '.$k.' ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = 't.'.$kk.' = '.$k.'.'.$vv.' ';
                }
                if ($v['sql']) {
                    $tmp[] = $v['sql'] . ' ';
                    $par = array_merge($v['par'], $par);
                }
                $sql .= implode(' AND ', $tmp);
            }
        }

        if (count($this->filter)) {
            $sql .= 'WHERE '.implode(' AND ', $this->filter).' ';
        }
        if (count($this->relations)) {
            $sql .= 'GROUP BY t.'.implode(', t.', $this->definition->getPrimaryKey()).' ';
        }
        if ($this->order) {
            $sql .= 'ORDER BY '.$this->order.' ';
        }
        if ((int) $limit) {
            $sql .= 'LIMIT '.(int) $limit.' ';
        }
        if ((int) $limit && (int) $offset) {
            $sql .= 'OFFSET '.(int) $offset;
        }
        $this->result = $this->database->get($sql, $par, null, false, 'assoc', false);
        $this->ext = [];

        return $this;
    }
    /**
     * Filter results using a raw query.
     * @method where
     * @param  string $sql    the sql filter query
     * @param  array  $params any parameters (if needed for the query)
     * @return self
     */
    public function where($sql, array $params = [])
    {
        $this->filter[] = '('.$sql.')';
        $this->params = array_merge($this->params, array_values($params));
        $this->result = null;
        $this->ext = [];

        return $this;
    }
    /**
     * Order the result set.
     * @method order
     * @param  string  $order the order query
     * @param  boolean $raw   should the query be used as is, or parsed (defaults to false, which means parsing)
     * @return self
     */
    public function order($order, $raw = false)
    {
        if ($raw) {
            $this->order = $order;

            return $this;
        }

        if (!is_array($order)) {
            $order = explode(',', $order);
        }
        $order = array_map('trim', $order);

        $temp = [];
        foreach ($order as $f) {
            $f = explode(' ', $f, 2);
            $f = array_map('trim', $f);
            if (!isset($f[1]) || !in_array($f[1], ['asc', 'desc', 'ASC', 'DESC'])) {
                $f[1] = 'ASC';
            }
            if (in_array($f[0], $this->definition->getColumns())) {
                $temp[] = $f[0].' '.$f[1];
                continue;
            }
            if (strpos($f[0], '.')) {
                $t = explode('.', $f[0], 2);
                if (isset($this->relations[$t[0]]) &&
                    in_array($t[1], $this->relations[$t[0]]['table']->definition->getColumns())
                ) {
                    $temp[] = $f[0].' '.$f[1];
                }
            }
        }
        $this->order = implode(', ', $temp);

        return $this;
    }
    /**
     * Filter the result set by column.
     * @method filter
     * @param  string $column the column name to filter on (use <relationshipname>.<columnname> to filter on related tables)
     * @param  mixed $value   the desired column value (can be a primitive or an array)
     * @return self
     */
    public function filter($column, $value)
    {
        $column = explode('.', $column, 2);
        if (count($column) === 1 && in_array($column[0], $this->definition->getColumns())) {
            $column = 't.'.$column[0];
        } elseif (count($column) === 2 &&
            isset($this->relations[$column[0]]) &&
            in_array($column[1], $this->relations[$column[0]]['table']->definition->getColumns())
        ) {
            $column = implode('.', $column);
        } else {
            throw new ORMException('Invalid column: '.implode('.', $column));
        }

        if (!is_array($value)) {
            return $this->where($column.' = ?', [$value]);
        }
        if (isset($value['beg']) && isset($value['end'])) {
            return $this->where($column.' BETWEEN ? AND ?', [$value['beg'], $value['end']]);
        }
        if (count($value)) {
            return $this->where($column.' IN ('.implode(',', array_fill(0, count($value), '?')).')', $value);
        }

        return $this;
    }
    /**
     * Get the result set count.
     * @method count
     * @return int the row count
     */
    public function count()
    {
        $sql = 'SELECT COUNT(DISTINCT t.'.implode(', t.', $this->definition->getPrimaryKey()).') '.
                'FROM '.$this->definition->getName().' AS t ';
        $par = $this->params;
        foreach ($this->relations as $k => $v) {
            if ($v['pivot']) {
                $sql .= 'LEFT JOIN '.$v['pivot'].' AS '.$k.'_pivot ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = 't.'.$kk.' = '.$k.'_pivot.'.$vv.' ';
                }
                $sql .= implode(' AND ', $tmp);

                $sql .= 'LEFT JOIN '.$v['table']->definition->getName().' AS '.$k.' ON ';
                $tmp = [];
                foreach ($v['pivot_keymap'] as $kk => $vv) {
                    $tmp[] = $k.'.'.$vv.' = '.$k.'_pivot.'.$kk.' ';
                }
                $sql .= implode(' AND ', $tmp);
            } else {
                $sql .= 'LEFT JOIN '.$v['table']->definition->getName().' AS '.$k.' ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = 't.'.$kk.' = '.$k.'.'.$vv.' ';
                }
                if ($v['sql']) {
                    $tmp[] = $v['sql'] . ' ';
                    $par = array_merge($v['par'], $par);
                }
                $sql .= implode(' AND ', $tmp);
            }
        }
        if (count($this->filter)) {
            $sql .= 'WHERE '.implode(' AND ', $this->filter).' ';
        }
        return $this->database->one($sql, $par);
    }
    /**
     * Clears all filters, ordering, etc.
     * @method reset
     * @return self
     */
    public function reset()
    {
        $this->filter = [];
        $this->params = [];
        $this->result = null;
        $this->order = null;
        $this->ext = [];
        $this->new = [];
        $this->del = [];

        return $this;
    }

    // row processing
    protected function extend($key, array $data = null)
    {
        if (isset($this->ext[$key])) {
            return $this->ext[$key];
        }
        if ($data === null) {
            return;
        }

        return $this->ext[$key] = new TableRow($this, $data);
    }

    // array stuff - collection handling
    public function offsetGet($offset)
    {
        if ($this->result === null) {
            $this->select();
        }

        return $this->result->offsetExists($offset) ? $this->extend($offset, $this->result->offsetGet($offset)) : null;
    }
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            if (is_array($value)) {
                $this->create($value);

                return;
            }
            if ($value instanceof TableRow) {
                return $this->new[] = $value;
            }
            throw new ORMException('Invalid input to offsetSet');
        }
        if ($this->result === null) {
            $this->select();
        }
        if (!$this->offsetExists($offset)) {
            throw new ORMException('Invalid offset used with offsetSet', 404);
        }
        $temp = $this->offsetGet($offset);
        if (is_array($value)) {
            return $temp->fromArray($value);
        }
        if ($value instanceof TableRow) {
            $this->del[] = $temp;
            $this->new[] = $value;
            return;
        }
        throw new ORMException('Invalid input to offsetSet');
    }
    public function offsetExists($offset)
    {
        if ($this->result === null) {
            $this->select();
        }

        return $this->result->offsetExists($offset);
    }
    public function offsetUnset($offset)
    {
        if ($this->result === null) {
            $this->select();
        }
        if (!$this->offsetExists($offset)) {
            throw new ORMException('Invalid offset used with offsetUnset', 404);
        }
        $temp = $this->offsetGet($offset);
        if (!$temp) {
            throw new ORMException('Invalid offset used with offsetUnset', 404);
        }
        $this->del[] = $temp;
    }
    public function current()
    {
        if ($this->result === null) {
            $this->select();
        }

        return $this->extend($this->result->key(), $this->result->current());
    }
    public function key()
    {
        if ($this->result === null) {
            $this->select();
        }

        return $this->result->key();
    }
    public function next()
    {
        if ($this->result === null) {
            $this->select();
        }
        $this->result->next();
    }
    public function rewind()
    {
        if ($this->result === null) {
            $this->select();
        }
        $this->result->rewind();
    }
    public function valid()
    {
        if ($this->result === null) {
            $this->select();
        }

        return $this->result->valid();
    }

    /**
     * Get the current result set as an array.
     * @method toArray
     * @param  boolean $full should relations be fetched as well
     * @return array        the data
     */
    public function toArray($full = true)
    {
        $temp = [];
        foreach ($this as $k => $v) {
            $temp[$k] = $v->toArray($full);
        }

        return $temp;
    }
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Create a new row for the current table (will not be persisted until save() is invoked).
     * @method create
     * @param  array  $data array of values for each column
     * @return \vakata\orm\TableRow the new row
     */
    public function create(array $data = [])
    {
        $temp = new TableRow(clone $this, [], true);
        $temp->fromArray($data);

        return $this->new[] = $temp;
    }
    /**
     * Persist all changes to database.
     * @method save
     * @param  array   $data   optionally override all new elements with these values
     * @param  boolean $delete should removed items be deleted from the database (defaults to true)
     */
    public function save(array $data = [], $delete = true)
    {
        $trans = $this->database->begin();
        try {
            $ret = [];
            $ids = null;
            foreach ($this->new as $temp) {
                foreach ($data as $k => $v) {
                    $temp->{$k} = $v;
                }
                $ids = $temp->save();
                $ret[md5(serialize($ids))] = array_merge($temp->toArray(false), $ids);
            }
            foreach ($this as $temp) {
                foreach ($data as $k => $v) {
                    $temp->{$k} = $v;
                }
                $ids = $temp->save();
                $ret[md5(serialize($ids))] = array_merge($temp->toArray(false), $ids);
            }
            foreach ($this->del as $temp) {
                $ids = $delete ? $temp->delete() : $temp->getID();
                unset($ret[md5(serialize($ids))]);
            }
            $this->database->commit($trans);

            return $ret;
        } catch (DatabaseException $e) {
            $this->database->rollback($trans);
            throw $e;
        }
    }
    /**
     * Remove the current result set from the database.
     * @method delete
     */
    public function delete()
    {
        $trans = $this->database->begin();
        try {
            foreach ($this as $temp) {
                $temp->delete();
            }
            $this->database->commit($trans);
        } catch (DatabaseException $e) {
            $this->database->rollback($trans);
            throw $e;
        }
    }
}
