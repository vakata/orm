<?php
namespace vakata\orm;

use \vakata\database\DBInterface;
use \vakata\database\schema\Table;

/**
 * Manager ORM class
 */
class Manager
{
    protected $schema;
    protected $creator;
    protected $tableClassMap = [];
    protected $entities = [];
    protected $hashes = [];
    protected $added = [];
    protected $deleted = [];

    /**
     * Create an instance
     * @param  DBInterface       $schema  the database schema
     * @param  callable|null     $creator optional function used to create all necessary classes
     */
    public function __construct(DBInterface $schema, callable $creator = null)
    {
        $this->schema = $schema;
        $this->creator = $creator !== null ?
            $creator :
            function ($class) {
                return new $class();
            };
    }
    /**
     * Add a class by name and link it to a table
     * @param  string          $class      the class to create when reading from the table
     * @param  string          $table      the table name associated with the class
     * @return $this
     */
    public function addClass(string $class, string $table)
    {
        $this->tableClassMap[$table] = $class;
        return $this;
    }
    public function __call(string $table, array $args)
    {
        $collection = new Collection($this, $this->schema->{$table}());
        return !count($args) ?
            $collection :
            $collection->find($args[0]);
    }

    public function instance(string $tableName, array $data)
    {
        $table = $this->schema->definition($tableName);
        $pkey = [];
        foreach ($table->getPrimaryKey() as $field) {
            $pkey[$field] = $data[$field];
        }
        if (!isset($this->entities[$table->getName()])) {
            $this->entities[$table->getName()] = [];
            $this->hashes[$table->getName()] = [];
        }
        if (isset($this->entities[$table->getName()][json_encode($pkey)])) {
            return $this->entities[$table->getName()][json_encode($pkey)];
        }
        $inst = call_user_func($this->creator, $this->tableClassMap[$table->getName()] ?? \StdClass::class);
        $hash = [];
        foreach ($table->getColumns() as $column) {
            $hash[$column] = $data[$column] ?? null;
            $inst->{$column} = $data[$column] ?? null;
        }
        $hash = sha1(serialize($hash));
        foreach ($table->getRelations() as $name => $relation) {
            if (isset($data[$name])) {
                if ($relation->many) {
                    $inst->{$name} = new RelationCollection($this, $relation->table->getName(), array_map(function ($v) {
                        return $this->instance($relation->table->getName(), $v);
                    }, $data[$name]));
                } else {
                    $inst->{$name} = new RelationCollection($this, $relation->table->getName(), [$this->instance($relation->table->getName(), $data[$name])]);
                }
                continue;
            }
            $query = $this->schema->table($relation->table->getName());
            if ($relation->sql) {
                $query->where($relation->sql, $relation->par);
            }
            if ($relation->pivot) {
                $nm = null;
                foreach ($relation->table->getRelations() as $rname => $rdata) {
                    if ($rdata->pivot && $rdata->pivot->getName() === $relation->pivot->getName()) {
                        $nm = $rname;
                    }
                }
                if (!$nm) {
                    $nm = $table->getName();
                    $relation->table->manyToMany(
                        $table,
                        $relation->pivot,
                        $nm,
                        array_flip($relation->keymap),
                        $relation->pivot_keymap
                    );
                }
                foreach ($pkey as $k => $v) {
                    $query->filter($nm . '.' . $k, $v ?? null);
                }
            } else {
                foreach ($relation->keymap as $k => $v) {
                    $query->filter($v, $data[$k] ?? null);
                }
            }
            $inst->{$name} = new RelationCollection($this, $relation->table->getName(), $query);
        }
        $this->hashes[$table->getName()][json_encode($pkey)] = $hash;
        return $this->entities[$table->getName()][json_encode($pkey)] = $inst;
    }
    public function add($entity, string $table = null) {
        if (!$table) {
            $table = array_search(get_class($entity), $this->tableClassMap);
            if (!$table) {
                foreach ($this->entities as $t => $objects) {
                    if (($old = array_search($entity, $objects, true)) !== false) {
                        $table = $t;
                    }
                }
                if (!$table) {
                    throw new ORMException('No table');
                }
            }
        }
        $definition = $this->schema->definition($table);
        if (!$definition) {
            throw new ORMException('No definition');
        }
        if (!isset($this->added[$table])) {
            $this->added[$table] = [];
        }
        if (array_search($entity, $this->added[$table], true) === false) {
            $this->added[$table][] = $entity;
        }
    }
    public function remove($entity, string $table = null) {
        if (!$table) {
            $table = array_search(get_class($entity), $this->tableClassMap);
            if (!$table) {
                foreach ($this->entities as $t => $objects) {
                    if (($old = array_search($entity, $objects, true)) !== false) {
                        $table = $t;
                    }
                }
                if (!$table) {
                    throw new ORMException('No table');
                }
            }
        }
        $definition = $this->schema->definition($table);
        if (!$definition) {
            throw new ORMException('No definition');
        }
        $pkey = [];
        foreach ($table->getPrimaryKey() as $field) {
            $pkey[$field] = $entity->{$field} ?? null;
        }
        if (!isset($this->added[$table])) {
            $this->deleted[$table] = [];
        }
        $this->deleted[$table][json_encode($pkey)] = $entity;
    }

    /**
     * Persist an instance to DB
     * @param  mixed $entity the instance object
     * @return array         the instance's primary key
     */
    public function save($entity, string $table = null) : array
    {
        $old = null;
        if (!$table) {
            $table = array_search(get_class($entity), $this->tableClassMap);
            if (!$table) {
                foreach ($this->entities as $t => $objects) {
                    if (($old = array_search($entity, $objects, true)) !== false) {
                        $table = $t;
                    }
                }
                if (!$table) {
                    throw new ORMException('No table');
                }
            }
        }
        $definition = $this->schema->definition($table);
        if (!$definition) {
            throw new ORMException('No definition');
        }
        $data = [];
        foreach ($definition->getColumns() as $column) {
            $data[$column] = $entity->{$column} ?? null;
        }
        // primary keys
        if (!isset($this->entities[$definition->getName()])) {
            $this->entities[$definition->getName()] = [];
            $this->hashes[$definition->getName()] = [];
        }
        if (!$old) {
            $old = array_search($entity, $this->entities[$definition->getName()], true);
        }
        if ($old !== false) {
            $old = json_decode($old, true);
        }
        $new = [];
        foreach ($definition->getPrimaryKey() as $field) {
            $new[$field] = $entity->{$field} ?? null;
        }

        // gather values from relations and set local fields
        foreach ($definition->getRelations() as $name => $relation) {
            if (count(array_diff(array_keys($relation->keymap), array_keys($new))) && ($entity->{$name} ?? null)) {
                $obj = $entity->{$name};
                $modified = false;
                if ($obj instanceof RelationCollection) {
                    $modified = $obj->isModified();
                    if ($modified) {
                        $obj = isset($obj[0]) ? $obj[0] : null;
                    }
                } else {
                    $modified = true;
                }
                if ($modified) {
                    if ($this->hasPrimaryKeyChanged($obj, $relation->table->getName())) {
                        $this->save($obj, $relation->table->getName());
                    }
                    foreach ($relation->keymap as $local => $remote) {
                        $data[$local] = $obj ? $obj->{$remote} : null;
                    }
                }
            }
        }
        $q = $this->schema->table($definition->getName());
        if ($old === false) {
            $id = $q->insert($data);
            foreach ($id as $fk => $fv) {
                $entity->{$fk} = $fv;
                $new[$fk] = $fv;
            }
            $this->entities[$definition->getName()][json_encode($new)] = $entity;
        } else {
            foreach ($old as $k => $v) {
                $q->filter($k, $v);
            }
            $q->update($data);
            if (json_encode($new) !== json_encode($old)) {
                unset($this->entities[$definition->getName()][json_encode($old)]);
                unset($this->hashes[$definition->getName()][json_encode($old)]);
                $this->entities[$definition->getName()][json_encode($new)] = $entity;
            }
        }
        $data = [];
        foreach ($definition->getColumns() as $column) {
            $data[$column] = $entity->{$column} ?? null;
        }
        $hash = sha1(serialize($data));
        $this->hashes[$definition->getName()][json_encode($new)] = $hash;

        foreach ($definition->getRelations() as $name => $relation) {
            if (!count(array_diff(array_keys($relation->keymap), array_keys($new))) && ($entity->{$name} ?? null)) {
                $obj = $entity->{$name};
                if (!$relation->pivot) {
                    $modified = !($obj instanceof RelationCollection) || $obj->isModified();
                    if ($old === false || json_encode($new) !== json_encode($old)) { // only on new ID
                        $modified = true;
                        if ($obj instanceof \Traversable || is_array($obj)) {
                            foreach ($obj as $o) {
                                foreach ($relation->keymap as $local => $remote) {
                                    $o->{$remote} = $new[$local];
                                }
                            }
                        } else if ($obj instanceof \StdClass) {
                            try {
                                foreach ($relation->keymap as $local => $remote) {
                                    $obj->{$remote} = $new[$local];
                                }
                            } catch (\Exception $ignore) { }
                        }
                        if ($old !== false) {
                            $query = $this->schema->table($relation->table->getName());
                            $data = [];
                            foreach ($relation->keymap as $local => $remote) {
                                $query->filter($remote, $old[$local]);
                                $data[$remote] = $new[$local];
                            }
                            $query->update($data);
                        }
                    }
                    if ($modified) {
                        if ($obj instanceof \Traversable || is_array($obj)) {
                            foreach ($obj as $o) {
                                $this->save($o, $relation->table->getName());
                            }
                        } else if ($obj instanceof \StdClass) {
                            try {
                                $this->save($obj, $relation->table->getName());
                            } catch (\Exception $ignore) { }
                        }
                    }
                } else {
                    // if the primary key is changed - update the pivot table
                    if ($old !== false && json_encode($new) !== json_encode($old)) {
                        $query = $this->schema->table($relation->pivot->getName());
                        $data = [];
                        foreach ($relation->keymap as $local => $remote) {
                            $query->filter($remote, $old[$local]);
                            $data[$remote] = $new[$local];
                        }
                        $query->update($data);
                    }
                    // if the collection is modified - update pivot table
                    if (!($obj instanceof RelationCollection) || $obj->isModified()) {
                        $query = $this->schema->table($relation->pivot->getName());
                        $data = [];
                        foreach ($relation->keymap as $local => $remote) {
                            $query->filter($remote, $new[$local]);
                            $data[$remote] = $new[$local];
                        }
                        $query->delete();
                        foreach ($entity->{$name} as $obj) {
                            $query->reset();
                            foreach ($relation->pivot_keymap as $local => $remote) {
                                $data[$local] = $obj->{$remote};
                            }
                            $query->insert($data);
                        }
                    }
                }
            }
        }
        return $new;
    }
    /**
     * Remove an instance from DB
     * @param  mixed $entity the instance to remove
     * @return int           the deleted rows count
     */
    public function delete($entity, string $table = null) : int
    {
        if (!$table) {
            $table = array_search(get_class($entity), $this->tableClassMap);
            foreach ($this->entities as $t => $objects) {
                if (($old = array_search($entity, $objects, true)) !== false) {
                    $table = $t;
                }
            }
            if (!$table) {
                throw new ORMException('No table');
            }
        }
        $definition = $this->schema->definition($table);
        if (!$definition) {
            throw new ORMException('No definition');
        }
        
        // should there be a check if the entity exists in storage?
        // if (!isset($this->entities[$definition->getName()])) {
        //     throw new Exception('Entity not found');
        // }
        // $pk = array_search($entity, $this->entities[$definition->getName()], true);
        // if ($pk === false) {
        //     throw new Exception('Entity not found');
        // }
        // $pk = json_decode($pk, true);

        $pk = [];
        foreach ($definition->getPrimaryKey() as $field) {
            $pk[$field] = $entity->{$field};
        }

        $q = $this->schema->table($definition->getName());
        foreach ($pk as $field => $value) {
            $q->filter($field, $value);
        }
        $res = $q->delete();
        // delete relations (might not be necessary - FK may have already deleted those)
        foreach ($definition->getRelations() as $name => $relation) {
            if ($relation->pivot) {
                $query = $this->schema->table($relation->pivot->getName());
                foreach ($relation->keymap as $local => $remote) {
                    $query->filter($remote, $pk[$local]);
                }
                $query->delete();
            } else {
                if (!count(array_diff(array_keys($relation->keymap), array_keys($pk)))) {
                    $query = $this->schema->table($relation->table->getName());
                    if ($relation->sql) {
                        $query->where($relation->sql, $relation->par);
                    }
                    foreach ($relation->keymap as $local => $remote) {
                        $query->filter($remote, $pk[$local]);
                    }
                    foreach ($query->select($relation->table->getPrimaryKey()) as $row) {
                        $key = [];
                        foreach ($relation->table->getPrimaryKey() as $field) {
                            $key[$field] = $row[$field] ?? null;
                        }
                        if (isset($this->entities[$relation->table->getName()]) &&
                            isset($this->entities[$relation->table->getName()][json_encode($key)])
                        ) {
                            unset($this->entities[$relation->table->getName()][json_encode($key)]);
                            unset($this->hashes[$relation->table->getName()][json_encode($key)]);
                        }
                    }
                    $query->delete();
                }
            }
        }
        if (isset($this->entities[$definition->getName()]) &&
            isset($this->entities[$definition->getName()][json_encode($pk)])
        ) {
            unset($this->entities[$definition->getName()][json_encode($pk)]);
            unset($this->hashes[$definition->getName()][json_encode($pk)]);
        }
        return $res;
    }

    protected function hasChanged($entity, string $table = null) : bool
    {
        if (!$table) {
            $table = array_search(get_class($entity), $this->tableClassMap);
            foreach ($this->entities as $t => $objects) {
                if (($old = array_search($entity, $objects, true)) !== false) {
                    $table = $t;
                }
            }
            if (!$table) {
                throw new ORMException('No table');
            }
        }
        $definition = $this->schema->definition($table);
        if (!$definition) {
            throw new ORMException('No definition');
        }
        $pk = array_search($entity, $this->entities[$table], true);
        $data = [];
        foreach ($definition->getColumns() as $column) {
            $data[$column] = $entity->{$column} ?? null;
        }
        return  $pk === false ||
                !isset($this->hashes[$table]) ||
                !isset($this->hashes[$table][$pk]) ||
                sha1(serialize($data)) !== $this->hashes[$table][$pk];
    }
    protected function hasPrimaryKeyChanged($entity, string $table = null) : bool
    {
        if (!$table) {
            $table = array_search(get_class($entity), $this->tableClassMap);
            foreach ($this->entities as $t => $objects) {
                if (array_search($entity, $objects, true) !== false) {
                    $table = $t;
                    break;
                }
            }
            if (!$table) {
                throw new ORMException('No table');
            }
        }
        $definition = $this->schema->definition($table);
        if (!$definition) {
            throw new ORMException('No definition');
        }
        $old = array_search($entity, $this->entities[$table], true);
        $pk = [];
        foreach ($definition->getPrimaryKey() as $field) {
            $pk[$field] = $entity->{$field};
        }
        return $old === false || json_encode($pk) !== $old;
    }

    public function saveChanges()
    {
        $this->schema->begin();
        try {
            foreach ($this->added as $table => $objects) {
                foreach ($objects as $instance) {
                    $this->save($instance, $table);
                }
            }
            $this->added = [];
            foreach ($this->entities as $table => $objects) {
                $definition = $this->schema->definition($table);
                foreach ($objects as $pk => $instance) {
                    if ($this->hasChanged($instance, $table)) {
                        $this->save($instance, $table);
                    }
                }
            }
            foreach ($this->deleted as $table => $objects) {
                foreach ($objects as $pk => $instance) {
                    if (isset($this->entities[$table][$pk])) {
                        unset($this->entities[$table][$pk]);
                        unset($this->hashes[$table][$pk]);
                    }
                    $this->delete($instance, $table);
                }
            }
            $this->deleted = [];
            $this->schema->commit();
        } catch (\Exception $e) {
            $this->schema->rollback();
            throw $e;
        }
    }
}
