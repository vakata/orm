<?php

namespace vakata\orm;

use vakata\dabatase\DatabaseException;

class TableRow implements TableRowInterface
{
    protected $table = null;
    protected $database = null;
    protected $definition = null;
    protected $relations = [];

    protected $isNew = false;
    protected $originalID = null;

    protected $data = [];
    protected $chng = [];
    protected $cche = [];

    public function __construct(TableInterface $table, array $data = [], $isNew = false)
    {
        $this->table = $table;
        $this->database = $table->getDatabase();
        $this->definition = $table->getDefinition();
        $this->relations = $table->getRelations();

        $this->isNew = $isNew;
        foreach ($this->definition->getColumns() as $column) {
            if (isset($data[$column])) {
                $this->data[$column] = $data[$column];
            }
        }
        $this->originalID = $this->getID();
        foreach ($data as $k => $v) {
            if (!isset($this->data[$k])) {
                $this->data[str_replace('___', '.', $k)] = $v;
            }
        }
    }

    public function getID()
    {
        $temp = [];
        foreach ($this->definition->getPrimaryKey() as $pkField) {
            $temp[$pkField] = $this->{$pkField};
        }

        return $temp;
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
            $temp = $this->{$key}();
            if (isset($temp)) {
                return $this->relations[$key]['many'] ? $temp : (isset($temp[0]) ? $temp[0] : null);
            }
        }

        return;
    }
    public function __call($key, $args)
    {
        if (!isset($this->relations[$key])) {
            return;
        }
        $ckey = $key;
        if (count($args)) {
            $ckey .= '_'.md5(serialize($args));
        }
        if (isset($this->cche[$ckey])) {
            return $this->cche[$ckey];
        }

        $relation = $this->relations[$key];
        $table = clone $relation['table'];

        if ($relation['pivot']) {
            $sql = 'SELECT '.implode(',', array_keys($relation['pivot_keymap'])).' FROM '.$relation['pivot'].' WHERE ';
            $par = [];
            $tmp = [];
            foreach ($relation['keymap'] as $k => $v) {
                $tmp[] = ' '.$v.' = ? ';
                $par[] = $this->{$k};
            }
            $sql .= implode(' AND ', $tmp);
            $ids = $this->database->all($sql, $par);

            if (!count($ids)) {
                return $this->cche[$ckey] = $table->where('1 = 0')->select();
            }

            return $this->cche[$ckey] = $table->where(
                ' ('.implode(',', $relation['pivot_keymap']).') IN ('.
                implode(',', array_fill(0, count($ids), (count($relation['pivot_keymap']) === 1 ?
                '?' : '('.implode(',', array_fill(0, count($relation['pivot_keymap']), '?')).
                ')'))).')',
                count($relation['pivot_keymap']) === 1 ? $ids : call_user_func_array('array_merge', $ids)
            )->select();
        }
        $sql = [];
        $par = [];
        foreach ($relation['keymap'] as $k => $v) {
            $sql[] = ' '.$v.' = ? ';
            $par[] = $this->{$k};
        }

        return $this->cche[$ckey] = $table->where(implode(' AND ', $sql), $par)->select();
    }

    public function toArray($full = true)
    {
        $temp = array_merge($this->data, $this->chng);
        if ($full) {
            foreach (array_keys($this->relations) as $k) {
                if ($this->{$k}) {
                    $temp[$k] = $this->{$k}->toArray(true);
                }
            }
        }

        return $temp;
    }
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function fromArray(array $data)
    {
        foreach ($data as $k => $v) {
            $this->__set($k, $v);
        }
    }
    public function __set($key, $value)
    {
        if (in_array($key, $this->definition->getColumns()) &&
            (isset($this->chng[$key]) || !isset($this->data[$key]) || $this->data[$key] !== $value)
        ) {
            $this->chng[$key] = $value;
        }
        if (isset($this->relations[$key])) {
            $temp = $this->{$key}();
            if ($temp) {
                foreach ($temp as $k => $v) {
                    unset($temp[$k]);
                }
                if ($value !== null) {
                    $temp[] = $value;
                }
            }
        }
    }

    // modifiers
    public function save()
    {
        $trans = $this->database->begin();
        try {
            $fk = $this->getID();

            if (!$this->isNew) {
                $this->chng = array_merge($this->data, $this->chng);
            }

            // belongs relations (update if none of the local keys are part of the primary key)
            foreach ($this->relations as $k => $v) {
                if (!count(array_intersect($this->definition->getPrimaryKey(), array_keys($v['keymap'])))) {
                    if ($v['pivot']) {
                        $sql = [];
                        $par = [];
                        $que = [];
                        foreach ($v['keymap'] as $local => $remote) {
                            $sql[] = $remote.' = ? ';
                            $par[] = $this->{$local};
                        }
                        $que[] = ['DELETE FROM '.$v['pivot'].' WHERE '.implode(' AND ', $sql), $par];

                        foreach ($this->{$k}()->save([], false) as $item) {
                            $sql = [];
                            $par = [];
                            foreach ($v['keymap'] as $local => $remote) {
                                $sql[] = $remote;
                                $par[] = $this->{$local};
                            }
                            foreach ($v['pivot_keymap'] as $local => $remote) {
                                $sql[] = $local;
                                $par[] = isset($item[$remote]) ? $item[$remote] : null;
                            }
                            $que[] = [
                                'INSERT INTO '.$v['pivot'].' ('.implode(', ', $sql).') '.
                                'VALUES('.implode(', ', array_fill(0, count($par), '?')).')',
                                $par
                            ];
                        }

                        foreach ($que as $sql) {
                            $this->database->query($sql[0], $sql[1]);
                        }
                    } else {
                        foreach ($this->{$k}()->save() as $item) {
                            foreach ($v['keymap'] as $local => $remote) {
                                if (isset($item[$remote])) {
                                    $this->chng[$local] = $item[$remote];
                                }
                            }
                        }
                    }
                }
            }

            // own data
            if (count($this->chng)) {
                if (!$this->isNew) {
                    $col = [];
                    $par = [];
                    $idf = [];
                    foreach ($this->chng as $k => $v) {
                        if (in_array($k, $this->definition->getColumns())) {
                            $col[] = $k.' = ?';
                            $par[] = $v;
                        }
                    }
                    if (count($col)) {
                        foreach ($this->definition->getPrimaryKey() as $pkField) {
                            $idf[] = $pkField.' = ? ';
                            $par[] = isset($this->originalID[$pkField]) ? $this->originalID[$pkField] : $fk[$pkField];
                        }
                        $this->database->query(
                            'UPDATE '.$this->definition->getName().' SET '.implode(', ', $col).' '.
                            'WHERE '.implode(' AND ', $idf),
                            $par
                        );
                    }
                } else {
                    $temp = [];
                    foreach ($this->chng as $k => $v) {
                        if (in_array($k, $this->definition->getColumns())) {
                            $temp[$k] = $v;
                        }
                    }
                    if (!count($temp)) {
                        throw new ORMException('Nothing to insert');
                    }
                    $iid = $this->database->query(
                        'INSERT INTO '.$this->definition->getName().' ('.implode(', ', array_keys($temp)).') '.
                        'VALUES ('.implode(', ', array_fill(0, count($temp), '?')).')',
                        array_values($temp)
                    )->insertId();
                    if (count($fk) === 1 && current($fk) === null) {
                        $fk[key($fk)] = $iid;
                    }
                }
            }

            // has relations (update if some of the local keys are part of the primary key)
            foreach ($this->relations as $k => $v) {
                if (count(array_intersect($this->definition->getPrimaryKey(), array_keys($v['keymap'])))) {
                    if ($v['pivot']) {
                        $sql = [];
                        $par = [];
                        $que = [];
                        foreach ($v['keymap'] as $local => $remote) {
                            $sql[] = $remote.' = ? ';
                            $par[] = isset($fk[$local]) ? $fk[$local] : $this->{$local};
                        }
                        $que[] = ['DELETE FROM '.$v['pivot'].' WHERE '.implode(' AND ', $sql), $par];

                        foreach ($this->{$k}()->save([], false) as $item) {
                            $sql = [];
                            $par = [];
                            foreach ($v['keymap'] as $local => $remote) {
                                $sql[] = $remote;
                                $par[] = isset($fk[$local]) ? $fk[$local] : $this->{$local};
                            }
                            foreach ($v['pivot_keymap'] as $local => $remote) {
                                $sql[] = $local;
                                $par[] = isset($item[$remote]) ? $item[$remote] : null;
                            }
                            $que[] = [
                                'INSERT INTO '.$v['pivot'].' ('.implode(', ', $sql).') '.
                                'VALUES('.implode(', ', array_fill(0, count($par), '?')).')',
                                $par
                            ];
                        }

                        foreach ($que as $sql) {
                            $this->database->query($sql[0], $sql[1]);
                        }
                    } else {
                        $data = [];
                        foreach ($v['keymap'] as $local => $remote) {
                            $data[$remote] = isset($fk[$local]) ? $fk[$local] : $this->{$local};
                        }
                        $this->{$k}()->save($data);
                    }
                }
            }

            $this->database->commit($trans);

            return $fk;
        } catch (DatabaseException $e) {
            $this->database->rollback($trans);
            throw $e;
        }
    }
    public function delete()
    {
        $trans = $this->database->begin();
        try {
            $fk = $this->getID();

            foreach ($this->relations as $k => $v) {
                if ($v['pivot']) {
                    $sql = [];
                    $par = [];
                    foreach ($v['keymap'] as $local => $remote) {
                        $sql[] = $remote.' = ? ';
                        $par[] = isset($fk[$local]) ? $fk[$local] : $this->{$local};
                    }
                    $this->database->query('DELETE FROM '.$v['pivot'].' WHERE '.implode(' AND ', $sql), $par);
                } else {
                    if ($this->definition->getPrimaryKey() == array_keys($v['keymap'])) {
                        foreach ($this->{$k}() as $item) {
                            $item->delete();
                        }
                    }
                }
            }
            if ($fk) {
                $sql = [];
                foreach ($fk as $k => $v) {
                    $sql[] = $k.' = ? ';
                }
                $this->database->query(
                    'DELETE FROM '.$this->definition->getName().' WHERE '.implode(' AND ', $sql),
                    $fk
                );
            }
            $this->database->commit($trans);

            return $fk;
        } catch (DatabaseException $e) {
            $this->database->rollback($trans);
            throw $e;
        }
    }
}
