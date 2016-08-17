<?php

namespace vakata\orm;

use vakata\database\DatabaseInterface;

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

    public function __construct(DatabaseInterface $db, TableDefinition $definition)
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
        $column = explode('.', $column, 2);
        if (count($column) === 1 && in_array($column[0], $this->definition->getColumns())) {
            $column = $this->definition->getName().'.'.$column[0];
        } elseif (
            count($column) === 2 &&
            $this->definition->hasRelation($column[0]) &&
            in_array($column[1], $this->definition->getRelation($column[0])['table']->getColumns())
        ) {
            $column = implode('.', $column);
        } else {
            throw new ORMException('Invalid column: '.implode('.', $column));
        }

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
    public function sort(string $column, bool $desc = false) : TableInterface
    {
        $column = explode('.', $column, 2);
        if (count($column) === 1 && in_array($column[0], $this->definition->getColumns())) {
            $column = $this->definition->getName().'.'.$column[0];
        } elseif (
            count($column) === 2 &&
            $this->definition->hasRelation($column[0]) &&
            in_array($column[1], $this->definition->getRelation($column[0])['table']->getColumns())
        ) {
            $column = implode('.', $column);
        } else {
            throw new ORMException('Invalid column: '.implode('.', $column));
        }

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
                $sql .= 'LEFT JOIN '.$v['pivot']->getName().' AS '.$k.'_pivot ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = $table.'.'.$kk.' = '.$k.'_pivot.'.$vv.' ';
                }
                $sql .= implode(' AND ', $tmp);
                $sql .= 'LEFT JOIN '.$v['table']->getName().' AS '.$k.' ON ';
                $tmp = [];
                foreach ($v['pivot_keymap'] as $kk => $vv) {
                    $tmp[] = $k.'.'.$vv.' = '.$k.'_pivot.'.$kk.' ';
                }
                $sql .= implode(' AND ', $tmp);
            } else {
                $sql .= 'LEFT JOIN '.$v['table']->getName().' AS '.$k.' ON ';
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
        return $this->db->one($sql, $par);
    }
    public function reset() : TableInterface
    {
        $this->where = [];
        $this->order = '';
        $this->limit = '';
        $this->result = null;
        $this->current = [];
        $this->changed = [];
        return $this;
    }

    protected function select()
    {
        $table = $this->definition->getName();
        $primary = $this->definition->getPrimaryKey();
        // TODO: fields listing, selecting relations?
        $sql = 'SELECT '.$table.'.* FROM '.$table.' ';
        $par = [];
        foreach ($this->definition->getRelations() as $k => $v) {
            if ($v['pivot']) {
                $sql .= 'LEFT JOIN '.$v['pivot']->getName().' AS '.$k.'_pivot ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = $table.'.'.$kk.' = '.$k.'_pivot.'.$vv.' ';
                }
                $sql .= implode(' AND ', $tmp);
                $sql .= 'LEFT JOIN '.$v['table']->getName().' AS '.$k.' ON ';
                $tmp = [];
                foreach ($v['pivot_keymap'] as $kk => $vv) {
                    $tmp[] = $k.'.'.$vv.' = '.$k.'_pivot.'.$kk.' ';
                }
                $sql .= implode(' AND ', $tmp);
            } else {
                $sql .= 'LEFT JOIN '.$v['table']->getName().' AS '.$k.' ON ';
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
        if ($this->definition->hasRelations()) {
            $sql .= 'GROUP BY '.$table.'.'.implode(', '.$table.'.', $primary).' ';
        }
        if ($this->order) {
            $sql = $this->order;
            $par = array_merge($par, $this->order[1]);
        }
        if ($this->limit) {
            $sql .= $this->limit;
        }
        $this->result = $this->db->get($sql, $par);
        $this->current = array_fill(0, count($this->result), null);
        $this->changed = array_fill(0, count($this->result), null);
        return $this;
    }
    public function where(string $sql, array $params = []) : TableInterface
    {
        $this->where[] = [ $sql, $params ];
        if ($this->result) {
            $this->result = null;
            $this->current = [];
            $this->changed = [];
        }
        return $this;
    }
    public function order(string $sql, array $params = []) : TableInterface
    {
        $this->order = [ $sql, $params ];
        if ($this->result) {
            $this->result = null;
            $this->current = [];
            $this->changed = [];
        }
        return $this;
    }
    public function limit(int $limit, int $offset = 0) : TableInterface
    {
        $this->limit = 'LIMIT ' . $limit . ' OFFSET ' . $offset;
        if ($this->result) {
            $this->result = null;
            $this->current = [];
            $this->changed = [];
        }
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
                $nm = null;
                foreach ($relation['table']->getRelations() as $rname => $rdata) {
                    if ($rdata['pivot'] && $rdata['pivot']->getName() === $relation['pivot']->getName()) {
                        $nm = $rname;
                    }
                }
                if (!$nm) {
                    $nm = $this->definition->getName();
                    //$relation['table']->hasRelation($this->definition->getName())) {
                    $relation['table']->manyToMany(
                        $this->definition,
                        $relation['pivot'],
                        $nm,
                        array_flip($relation['keymap']),
                        $relation['pivot_keymap']
                    );
                }
                foreach ($id as $k => $v) {
                    $relations[$name]->filter($nm . '.' . $k, $v ?? null)->select(true);
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
