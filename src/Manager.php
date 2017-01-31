<?php
namespace vakata\orm;

use \vakata\database\DatabaseInterface;
use \vakata\database\Table;

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
     * @param  Schema            $schema  the database schema
     * @param  callable|null     $creator optional function used to create all necessary classes
     */
    public function __construct(DatabaseInterface $schema, callable $creator = null)
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
            $hash[$column] = $hash[$column] ?? null;
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
        $this->deleted[$table][json_encode($pkey)] = $entity;
    }

    /**
     * Persist an instance to DB
     * @param  mixed $entity the instance object
     * @param  bool  $readRelations should related entities be read and update local entity fields, default to false
     * @return array         the instance's primary key
     */
    public function save($entity, bool $readRelations = false, string $table = null) : array
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
        $hash = sha1(serialize($data));
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
            $new[$field] = $entity->{$field};
        }

        // gather values from relations and set local fields
        if ($readRelations) {
            foreach ($definition->getRelations() as $name => $relation) {
                if (count(array_diff(array_keys($relation->keymap), array_keys($new)))) {
                    $obj = $entity->{$name}[0];
                    foreach ($relation->keymap as $local => $remote) {
                        $data[$local] = $obj->{$remote};
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
        $this->hashes[$definition->getName()][json_encode($new)] = $hash;

        foreach ($definition->getRelations() as $name => $relation) {
            if (!count(array_diff(array_keys($relation->keymap), array_keys($new)))) {
                if (!$relation->pivot) {
                    if ($old === false || json_encode($new) !== json_encode($old)) { // only on new ID
                        if (is_array($entity->{$name}) || $entity->{$name} instanceof \Traversable) {
                            foreach ($entity->{$name} as $obj) {
                                foreach ($relation->keymap as $local => $remote) {
                                    $obj->{$remote} = $new[$local];
                                }
                            }
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
                } else {
                    if ($old !== false && json_encode($new) !== json_encode($old)) { // only on new ID
                        $query = $this->schema->table($relation->pivot->getName());
                        $data = [];
                        foreach ($relation->keymap as $local => $remote) {
                            $query->filter($remote, $old[$local]);
                            $data[$remote] = $new[$local];
                        }
                        $query->update($data);
                    }
                    /*
                    $query = $this->schema->table($relation['pivot']->getName());
                    $data = [];
                    foreach ($relation['keymap'] as $local => $remote) {
                        $query->filter($remote, $new[$local]);
                        $data[$remote] = $new[$local];
                    }
                    $query->delete();
                    if (is_array($entity->{$name}) || $entity->{$name} instanceof \Traversable) {
                        foreach ($entity->{$name} as $obj) {
                            $query->reset();
                            foreach ($relation['pivot_keymap'] as $local => $remote) {
                                $data[$local] = $obj->{$remote};
                            }
                            $query->insert($data);
                        }
                    }
                    */
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

    public function saveChanges()
    {
        $this->schema->begin();
        try {
            foreach ($this->added as $table => $objects) {
                foreach ($objects as $instance) {
                    $this->save($instance, false, $table);
                }
            }
            foreach ($this->deleted as $table => $objects) {
                foreach ($objects as $pk => $instance) {
                    if (isset($this->entities[$table][$pk])) {
                        unset($this->entities[$table][$pk]);
                        unset($this->hashes[$table][$pk]);
                    }
                    $this->delete($instance, false, $table);
                }
            }
            foreach ($this->entities as $table => $objects) {
                $definition = $this->schema->definition($table);
                foreach ($objects as $pk => $instance) {
                    $data = [];
                    foreach ($definition->getColumns() as $column) {
                        $data[$column] = $entity->{$column} ?? null;
                    }
                    if (sha1(serialize($data)) !== $this->hashes[$table][$pk]) {
                        $this->save($instance, false, $table);
                    }
                }
            }
            $this->schema->commit();
        } catch (\Exception $e) {
            $this->schema->rollback();
            throw $e;
        }
    }
}
