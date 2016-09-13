<?php
namespace vakata\orm;

use vakata\database\DatabaseInterface;

class Query
{
    protected $db;
    protected $definition;

    protected $where = [];
    protected $order = [];
    protected $limit = '';

    protected $withr = [];

    public static function fromDatabase(DatabaseInterface $db, string $table)
    {
        return new static($db, TableDefinition::fromDatabase($db, $table));
    }

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
    public function sort(string $column, bool $desc = false) : Query
    {
        $column = $this->normalizeColumn($column);
        return $this->order('ORDER BY ' . $column . ' ' . ($desc ? 'DESC' : 'ASC'));
    }
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
    public function reset() : Query
    {
        $this->where = [];
        $this->order = [];
        $this->limit = '';
        return $this;
    }

    public function where(string $sql, array $params = []) : Query
    {
        $this->where[] = [ $sql, $params ];
        return $this;
    }
    public function order(string $sql, array $params = []) : Query
    {
        $this->order = [ $sql, $params ];
        return $this;
    }
    public function limit(int $limit, int $offset = 0) : Query
    {
        $this->limit = 'LIMIT ' . $limit . ' OFFSET ' . $offset;
        return $this;
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
    public function select(array $fields = null) : array
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
            $select[] = $field . (!is_numeric($k) ? ' AS ' . $k : '');
        }
        foreach ($this->withr as $relation) {
            foreach ($this->definition->getRelation($relation)['table']->getColumns() as $column) {
                $select[] = $relation . '.' . $column . ' AS ' . $relation . '___' . $column;
            }
        }
        $sql = 'SELECT '.implode(', ', $select).' FROM '.$table.' ';
        $par = [];
        foreach (array_unique($relations) as $relation) {
            $v = $this->definition->getRelation($relation);
            if ($v['pivot']) {
                $sql .= 'LEFT JOIN '.$v['pivot']->getName().' AS '.$relation.'_pivot ON ';
                $tmp = [];
                foreach ($v['keymap'] as $kk => $vv) {
                    $tmp[] = $table.'.'.$kk.' = '.$relation.'_pivot.'.$vv.' ';
                }
                $sql .= implode(' AND ', $tmp);
                $sql .= 'LEFT JOIN '.$v['table']->getName().' AS '.$relation.' ON ';
                $tmp = [];
                foreach ($v['pivot_keymap'] as $kk => $vv) {
                    $tmp[] = $relation.'.'.$vv.' = '.$relation.'_pivot.'.$kk.' ';
                }
                $sql .= implode(' AND ', $tmp);
            } else {
                $sql .= 'LEFT JOIN '.$v['table']->getName().' AS '.$relation.' ON ';
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
                $tmp[] = $v[0];
                $par = array_merge($par, $v[1]);
            }
            $sql .= implode(' AND ', $tmp).' ';
        }
        //if ($this->definition->hasRelations()) {
        //    $sql .= 'GROUP BY '.$table.'.'.implode(', '.$table.'.', $primary).' ';
        //}
        if (count($this->order)) {
            $sql .= $this->order[0];
            $par = array_merge($par, $this->order[1]);
        }
        if ($this->limit) {
            $sql .= $this->limit;
        }
        $result = [];
        foreach ($this->db->get($sql, $par) as $row) {
            $pk = [];
            foreach ($primary as $field) {
                $pk[$field] = $row[$field];
            }
            $pk = json_encode($pk);
            if (!isset($result[$pk])) {
                $result[$pk] = $row;
            }
            foreach ($this->withr as $relation) {
                $temp = $this->definition->getRelation($relation);
                if (!isset($result[$pk][$relation])) {
                    $result[$pk][$relation] = $temp['many'] ? [] : null;
                }
                $fields = [];
                $exists = false;
                foreach ($temp['table']->getColumns() as $column) {
                    $fields[$column] = $row[$relation . '___' . $column];
                    if (!$exists && $row[$relation . '___' . $column] !== null) {
                        $exists = true;
                    }
                    unset($result[$pk][$relation . '___' . $column]);
                }
                if ($exists) {
                    if ($temp['many']) {
                        $rpk = [];
                        foreach ($temp['table']->getPrimaryKey() as $field) {
                            $rpk[$field] = $fields[$field];
                        }
                        $result[$pk][$relation][json_encode($rpk)] = $fields;
                    } else {
                        $result[$pk][$relation] = $fields;
                    }
                }
            }
        }
        $result = array_values($result);
        foreach ($result as $k => $row) {
            foreach ($this->withr as $relation) {
                $temp = $this->definition->getRelation($relation);
                if ($temp['many']) {
                    $result[$k][$relation] = array_values($result[$k][$relation]);
                }
            }
        }
        return $result;
    }
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

    public function with(string $relation) : Query
    {
        if (!$this->definition->hasRelation($relation)) {
            throw new ORMException('Invalid relation name');
        }
        $this->withr[$relation] = $relation;
        return $this;
    }
}
