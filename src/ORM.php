<?php

namespace orm;

use vakata\database\DatabaseInterface;

class Manager
{
    protected $db;
    protected $aliases = [];
    protected $definitions = [];
    protected $entities = [];

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function addDefinition(string $class, TableDefinition $table, string $name = null)
    {
        if (!$name) {
            $name = $table->getName();
        }
        $this->aliases[$name] = $table;
        $this->definitions[trim($class, '\\')] = $table;
    }

    public function __call($repository, $args)
    {
        if (!isset($this->aliases[$repository])) {
            throw new ORMException('Invalid repository');
        }
        return new Table($this->db, $this->aliases[$repository]);
    }
    public function save($entity)
    {
        foreach ($this->definitions as $class => $table) {
            if ($entity instanceof $class) {
                (new Table($this->db, $table))->save($entity);
            }
        }
        throw new ORMException('Could not store object');
    }
    public function delete($entity)
    {
        foreach ($this->definitions as $class => $table) {
            if ($entity instanceof $class) {
                (new Table($this->db, $table))->delete($entity);
            }
        }
        throw new ORMException('Could not delete object');
    }
    public function register($entity, $id)
    {
        if (!isset($this->definitions[$class])) {
            throw new ORMException('Unknown class');
        }
        $this->entities[get_class($entity) . '\\' . $id] = $entity;
    }
}

interface TableInterface extends \Iterator, \ArrayAccess, \Countable
{
    public function getDefinition() : TableDefinition;

    public function filter(string $column, $value) : TableInterface;
    public function sort(string $column, bool $desc = false) : TableInterface;
    public function paginate(int $page = 1, int $perPage = 25) : TableInterface;
    public function reset() : TableInterface;
    public function count() : int;
    
    public function where(string $sql, array $params = []) : TableInterface;
    public function order(string $sql, array $params = []) : TableInterface;
    public function limit(int $limit, int $offset = 0) : TableInterface;

    public function create(array $data = []) : TableRowInterface;
    public function save(TableRowInterface $row) : TableRowInterface;
    public function delete(TableRowInterface $row) : TableRowInterface;
}

interface TableRowInterface
{
    public function toArray($full = true);
}

class ORMException extends \Exception
{
}

class TableDefinition
{
    protected $data = [];
    protected $relations = [];

    public static function fromTableName(DatabaseInterface $db, string $table)
    {
        $definition = new TableDefinition($table);
        $columns = [];
        $primary = [];
        switch ($db->driver()) {
            case 'mysql':
            case 'mysqli':
                foreach ($db->all('SHOW FULL COLUMNS FROM '.$table) as $data) {
                    $columns[$data['Field']] = $data;
                    if ($data['Key'] == 'PRI') {
                        $primary[] = $data['Field'];
                    }
                }
                break;
            case 'postgre':
            case 'oracle':
                $columns = $db->all(
                    'SELECT * FROM information_schema.columns WHERE table_name = ?',
                    [ $table ],
                    'column_name'
                );
                $tmp = $db->one(
                    'SELECT constraint_name FROM information_schema.table_constraints '.
                    'WHERE table_name = ? AND constraint_type = ?',
                    [ $table, 'PRIMARY KEY' ]
                );
                if ($tmp) {
                    $primary = $db->all(
                        'SELECT column_name FROM information_schema.key_column_usage WHERE table_name = ? AND constraint_name = ?',
                        [ $table, $tmp ]
                    );
                }
                break;
            default:
                throw new ORMException('Driver is not supported: '.$database->driver(), 500);
        }
        // TODO: foreign keys
        return $definition
            ->addColumns($columns)
            ->setPrimaryKey($primary);
    }

    public function __construct(string $name)
    {
        $this->data = [
            'name'    => $name,
            'columns' => [],
            'primary' => []
        ];
        $this->relations = [];
    }
    public function addColumn(string $column, array $definition = []) : TableDefinition
    {
        $this->data['columns'][$column] = $definition;
        return $this;
    }
    public function addColumns(array $columns) : TableDefinition
    {
        foreach ($columns as $column => $definition) {
            if (is_numeric($column) && is_string($definition)) {
                $this->addColumn($definition, []);
            } else {
                $this->addColumn($column, $definition);
            }
        }
        return $this;
    }
    public function setPrimaryKey($column) : TableDefinition
    {
        if (!is_array($column)) {
            $column = [ $column ];
        }
        $this->data['primary'] = $column;
        return $this;
    }
    public function getName()
    {
        return $this->data['name'];
    }
    public function getColumn($column)
    {
        return $this->data['columns'][$column] ?? null;
    }
    public function getColumns()
    {
        return array_keys($this->data['columns']);
    }
    public function getFullColumns()
    {
        return $this->data['columns'];
    }
    public function getPrimaryKey()
    {
        return $this->data['primary'];
    }
    public function hasOne(
        TableDefinition $toTable,
        string $name = null,
        $toTableColumn = null,
        string $sql = null,
        array $par = []
    ) : TableDefinition
    {
        $toTable = $this->getRelatedTable($toTable);
        $columns = $toTable->getColumns();

        $keymap = [];
        if (!isset($toTableColumn)) {
            $toTableColumn = [];
        }
        if (!is_array($toTableColumn)) {
            $toTableColumn = [$toTableColumn];
        }
        foreach ($this->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($toTableColumn[$pkField])) {
                $key = $toTableColumn[$pkField];
            } elseif (isset($toTableColumn[$k])) {
                $key = $toTableColumn[$k];
            } else {
                $key = $this->getName().'_'.$pkField;
            }
            if (!in_array($key, $columns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $keymap[$pkField] = $key;
        }

        if (!isset($name)) {
            $name = $toTable->getName() . '_' . implode('_', array_keys($keymap));
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
    public function hasMany(
        TableDefinition $toTable,
        string $name = null,
        $toTableColumn = null,
        $sql = null,
        array $par = []
    ) : TableDefinition
    {
        $columns = $toTable->getColumns();

        $keymap = [];
        if (!isset($toTableColumn)) {
            $toTableColumn = [];
        }
        if (!is_array($toTableColumn)) {
            $toTableColumn = [$toTableColumn];
        }
        foreach ($this->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($toTableColumn[$pkField])) {
                $key = $toTableColumn[$pkField];
            } elseif (isset($toTableColumn[$k])) {
                $key = $toTableColumn[$k];
            } else {
                $key = $this->getName().'_'.$pkField;
            }
            if (!in_array($key, $columns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $keymap[$pkField] = $key;
        }

        if (!isset($name)) {
            $name = $toTable->getName().'_'.implode('_', array_keys($keymap));
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
    public function belongsTo(
        TableDefinition $toTable,
        string $name = null,
        $localColumn = null,
        $sql = null,
        array $par = []
    ) : TableDefinition
    {
        $columns = $this->getColumns();

        $keymap = [];
        if (!isset($localColumn)) {
            $localColumn = [];
        }
        if (!is_array($localColumn)) {
            $localColumn = [$localColumn];
        }
        foreach ($toTable->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($localColumn[$pkField])) {
                $key = $localColumn[$pkField];
            } elseif (isset($localColumn[$k])) {
                $key = $localColumn[$k];
            } else {
                $key = $toTable->getName().'_'.$pkField;
            }
            if (!in_array($key, $columns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $keymap[$key] = $pkField;
        }

        if (!isset($name)) {
            $name = $toTable->getName().'_'.implode('_', array_keys($keymap));
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
    public function manyToMany(
        TableDefinition $toTable,
        TableDefinition $pivot,
        $name = null,
        $toTableColumn = null,
        $localColumn = null
    ) : TableDefinition
    {
        $pivotColumns = $pivot->getColumns();

        $keymap = [];
        if (!isset($toTableColumn)) {
            $toTableColumn = [];
        }
        if (!is_array($toTableColumn)) {
            $toTableColumn = [$toTableColumn];
        }
        foreach ($this->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($toTableColumn[$pkField])) {
                $key = $toTableColumn[$pkField];
            } elseif (isset($toTableColumn[$k])) {
                $key = $toTableColumn[$k];
            } else {
                $key = $this->getName().'_'.$pkField;
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
        foreach ($toTable->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($localColumn[$pkField])) {
                $key = $localColumn[$pkField];
            } elseif (isset($localColumn[$k])) {
                $key = $localColumn[$k];
            } else {
                $key = $toTable->getName().'_'.$pkField;
            }
            if (!in_array($key, $pivotColumns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $pivotKeymap[$key] = $pkField;
        }

        if (!isset($name)) {
            $name = $toTable->getName().'_'.implode('_', array_keys($keymap));
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
    public function hasRelations() : bool
    {
        return count($this->relations) > 0;
    }
    public function getRelations() : array
    {
        return $this->relations;
    }
    public function hasRelation(string $name) : bool
    {
        return isset($this->relations[$name]);
    }
    public function getRelation(string $name) : array
    {
        return $this->relations[$name] ?? null;
    }
}

class Table implements TableInterface
{
    protected $db;
    protected $definition;

    protected $where = [];
    protected $order = '';
    protected $limit = '';

    protected $result = null;
    protected $current = [];
    protected $changed = [];

    public function __construct(DatabaseInterface $db, TableDefinition $table)
    {
        $this->db = $db;
        $this->definition = $definition;
    }
    public function __clone()
    {
        $this->reset();
    }
    public function getDefinition() : TableDefinition
    {
        return $this->definition;
    }

    public function filter(string $column, $value) : TableInterface
    {
        return $this->where($column . ' = ?', [ $value ]);
    }
    public function sort(string $column, bool $desc = false) : TableInterface
    {
        return $this->order('ORDER BY ' . $column . ($desc ? 'DESC' : 'ASC'));
    }
    public function paginate(int $page = 1, int $perPage = 25) : TableInterface
    {
        return $this->limit($perPage, ($page - 1) * $perPage);
    }
    public function count() : int
    {
        $table = $this->definition->getName();
        $primary = $this->definition->getPrimaryKey();
        $sql = 'SELECT COUNT(DISTINCT '.$table.'.'.implode(', '.$table.'.', $primary).') FROM '.$table.' ';
        $par = [];
        foreach ($this->definition->getRelations() as $k => $v) {
            if ($v['pivot']) {
                $sql .= 'LEFT JOIN '.$v['pivot'].' AS '.$k.'_pivot ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = $table.'.'.$kk.' = '.$k.'_pivot.'.$vv.' ';
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
                $tmp[] = $v[0];
                $par = array_merge($par, $v[0]);
            }
            $sql .= implode(' AND ', $tmp).' ';
        }
        return $this->database->one($sql, $par);
    }
    public function reset() : TableInterface
    {
        $this->where = [];
        $this->order = '';
        $this->limit = '';
        $this->result = null;
        $this->current = [];
    }

    protected function select()
    {
        $table = $this->definition->getName();
        $primary = $this->definition->getPrimaryKey();
        $sql = 'SELECT '.$table.'.* FROM '.$table.' ';
        $par = [];
        foreach ($this->definition->getRelations() as $k => $v) {
            if ($v['pivot']) {
                $sql .= 'LEFT JOIN '.$v['pivot'].' AS '.$k.'_pivot ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = $table.'.'.$kk.' = '.$k.'_pivot.'.$vv.' ';
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
                $tmp[] = $v[0];
                $par = array_merge($par, $v[1]);
            }
            $sql .= implode(' AND ', $tmp).' ';
        }
        if ($this->defintion->hasRelations()) {
            $sql .= 'GROUP BY '.$table.'.'.implode(', '.$table.'.', $primary).' ';
        }
        if (count($this->order)) {
            $sql .= $this->order[0];
            $par = array_merge($par, $this->order[1]);
        }
        if ($this->limit) {
            $sql .= $this->limit;
        }
        $this->result = $this->database->get($sql, $par);
        $this->current = array_fill(0, count($this->result), null);
        $this->changed = array_fill(0, count($this->result), null);
        return $this;
    }
    public function where(string $sql, array $params = []) : TableInterface
    {
        $this->where[] = [ $sql, $params ];
        return $this;
    }
    public function order(string $sql, array $params = []) : TableInterface
    {
        $this->order = [ $sql, $params ];
        return $this;
    }
    public function limit(int $limit, int $offset = 0) : TableInterface
    {
        $this->limit = 'LIMIT ' . $limit . ' OFFSET ' . $offset;
        return $this;
    }

    public function create(array $data = []) : TableRowInterface
    {
        $id = [];
        foreach ($this->definition->getPrimaryKey() as $field) {
            $id[$field] = $data[$field] ?? null;
        }
        $relations = [];
        foreach ($this->definition->getRelations() as $name => $relation) {
            $relations[$name] = new Table($this->db, $relation['table']);
            $relations[$name]->many = $relation['many'];
            if ($relation['sql']) {
                $relations[$name]->where($relation['sql'], $relation['par']);
            }
            if ($relation['pivot']) {
                if (!$relation['table']->hasRelation($this->definition->getName())) {
                    $relation['table']->manyToMany(
                        $this->definition,
                        $relation['pivot'],
                        $this->definition->getName(),
                        $relation['keymap'],
                        $relation['pivot_keymap']
                    );
                }
                foreach ($relation['keymap'] as $k => $v) {
                    $relations[$name]->filter($this->definition->getName() . '.' . $v, $data[$k] ?? null);
                }
            } else {
                foreach ($relation['keymap'] as $k => $v) {
                    $relations[$name]->filter($v, $data[$k] ?? null);
                }
            }
        }
        return new TableRow($data, $relations);
    }
    public function save(TableRowInterface $row) : TableRowInterface
    {
    }
    public function delete(TableRowInterface $row) : TableRowInterface
    {
    }

    // array row processing
    protected function extend($key, array $data = null)
    {
        if (isset($this->current[$key])) {
            return $this->current[$key];
        }
        if ($data === null) {
            return;
        }
        return $this->current[$key] = $this->create($data); // new TableRow($this, $data);
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
        if (!is_array($value)) {
            throw new ORMException('Invalid input to offsetSet');
        }
        if ($this->result === null) {
            $this->select();
        }
        if ($offset === null) {
            $value = $this->create($value);
            return $this->changed[] = $value;
        }
        if (!$this->offsetExists($offset)) {
            throw new ORMException('Invalid offset used with offsetSet', 404);
        }
        $temp = $this->offsetGet($offset);
        foreach ($value as $k => $v) {
            $temp->__set($k, $v);
        }
        return $this->changed[$offset] = $temp;
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
        $this->changed[$offset] = false;
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
    // dumping
    public function toArray($full = true)
    {
        $temp = [];
        foreach ($this as $k => $v) {
            $temp[$k] = $v->toArray($full);
        }
        return $temp;
    }
}

class TableRow implements TableRowInterface
{
    protected $data = [];
    protected $chng = [];
    protected $relations = [];

    public function __construct(array $data = [], array $relations = [])
    {
        $this->db = $db;
        $this->data = $data;
        $this->relations = $relations;
    }
    public function __get($key)
    {
        if (isset($this->chng[$key])) {
            return $this->chng[$key];
        }
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        if (isset($this->relations[$key])) {
            return $this->relations[$key]->many ? $this->relations[$key] : ($this->relations[$key][0] ?? null);
        }
        return null;
    }
    public function __set($key, $value)
    {
        if (in_array($key, $this->definition->getColumns()) &&
            (isset($this->chng[$key]) || !isset($this->data[$key]) || $this->data[$key] !== $value)
        ) {
            $this->chng[$key] = $value;
        }
        if (isset($this->relations[$key])) {
            foreach ($this->relations[$key] as $k => $v) {
                unset($this->relations[$key][$k]);
            }
            if ($value !== null) {
                $this->relations[$key][] = $value;
            }
        }
    }
    public function __call($key, $args)
    {
        if (!isset($this->relations[$key])) {
            return null;
        }
        return $this->relations[$key];
    }

    public function toArray($full = true)
    {
        $temp = array_merge($this->data, $this->chng);
        if ($full) {
            foreach ($this->relations as $k => $v) {
                $temp[$k] = $v->toArray(true);
            }
        }
        return $temp;
    }
}