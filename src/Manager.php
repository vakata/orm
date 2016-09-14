<?php
namespace vakata\orm;

use vakata\database\DatabaseInterface;

// TODO: relation to the same table using differnt keymaps - in TableDefinition

/**
 * Manager ORM class
 */
class Manager
{
    protected $db;
    protected $creator;
    protected $classes = [];
    protected $entities = [];
    protected $definitions = [];

    /**
     * Create an instance
     * @method __construct
     * @param  DatabaseInterface $db      the database connection
     * @param  callable|null     $creator optional function used to create all necessary classes
     */
    public function __construct(DatabaseInterface $db, callable $creator = null)
    {
        $this->db = $db;
        $this->creator = $creator !== null ?
            $creator :
            function ($class) {
                return new $class();
            };
    }
    /**
     * Add a table definition to the manager (most of the time you can rely on the autodetected definitions)
     * @method addDefinition
     * @param  TableDefinition $definition the definition
     * @return  self
     */
    public function addDefinition(TableDefinition $definition)
    {
        if (!isset($this->definitions[$definition->getName()])) {
            $this->definitions[$definition->getName()] = $definition;
        }
        return $this;
    }
    /**
     * Autodetect a definition by table name and add it to the manager.
     * @method addDefinitionByTableName
     * @param  string                   $table the table to analyze
     * @return  self
     */
    public function addDefinitionByTableName(string $table)
    {
        if (!isset($this->definitions[$table])) {
            $this->addDefinition(TableDefinition::fromDatabase($this->db, $table));
        }
        return $this;
    }
    /**
     * Get an existing definition.
     * @method getDefinition
     * @param  string        $search the table name
     * @return TableDefinition|null                the definition of null if not found
     */
    public function getDefinition(string $search)
    {
        return $this->definitions[$search] ?? null;
    }
    /**
     * Add a class by name and link it to a definition
     * @method addClass
     * @param  string          $class      the class to create when reading from the table
     * @param  TableDefinition $definition the table definition associated with the class
     * @return  self
     */
    public function addClass(string $class, TableDefinition $definition)
    {
        if (!isset($this->definitions[$table->getName()])) {
            $this->addDefinition($definition);
        }
        $this->classes[$class] = $this->definitions[$definition->getName()];
        return $this;
    }
    /**
     * Add a class by name and link it to a table name
     * @method addClassByTableName
     * @param  string          $class      the class to create when reading from the table
     * @param  string          $table      the table name associated with the class
     * @return  self
     */
    public function addClassByTableName(string $class, string $table)
    {
        if (!isset($this->definitions[$table])) {
            $this->addDefinitionByTableName($table);
        }
        $this->classes[$class] = $this->definitions[$table];
        return $this;
    }
    protected function getClass(string $search)
    {
        foreach ($this->classes as $class => $definition) {
            if (strtolower($class) === strtolower($search)) {
                return $class;
            }
        }
        foreach ($this->classes as $class => $definition) {
            if (strtolower($definition->getName()) === strtolower($search)) {
                return $class;
            }
        }
        foreach ($this->classes as $class => $definition) {
            if (strtolower(basename(str_replace('\\', '/', $class))) === strtolower($search)) {
                return $class;
            }
        }
        return null;
    }

    public function __call(string $search, array $args)
    {
        $class = $this->getClass($search);
        if (!$class) {
            $this->addDefinitionByTableName($search, true);
            $class = Row::CLASS;
            $definition = $this->definitions[$search];
        } else {
            $definition = $this->classes[$class];
        }
        if (!count($args)) {
            return new Collection(new Query($this->db, $definition), $this, $class);
        }
        return $this->entity($class, $args, null, $definition);
    }
    /**
     * Create an instance
     * @method create
     * @param  string               $search     the type of instance to create (class name, table name, etc)
     * @param  array                $data       optional array of data to populate with (defaults to an empty array)
     * @param  TableDefinition|null $definition optional explicit definition to use
     * @return mixed                            the newly created instance
     */
    public function create(string $search, array $data = [], TableDefinition $definition = null)
    {
        $class = $this->getClass($search);
        if (!$class) {
            $class = Row::CLASS;
            if (!$definition) {
                if (!isset($this->definitions[$search])) {
                    $this->addDefinitionByTableName($search, true);
                }
                $definition = $this->definitions[$search];
            }
        }
        if (!$definition) {
            if (!isset($this->classes[$class])) {
                throw new ORMException('No definition');
            }
            $definition = $this->classes[$class];
        }
        $instance = call_user_func($this->creator, $class);
        if ($class === Row::CLASS) {
            $instance->__definition = $definition->getName();
        }
        foreach ($definition->getColumns() as $column) {
            $instance->{$column} = $data[$column] ?? null;
        }
        foreach ($definition->getRelations() as $name => $relation) {
            if ($relation['many'] === false && isset($data[$name])) {
                $data[$name] = [$data[$name]];
            }
            $instance->{$name} = $data[$name] ?? null;
        }
        return $instance;
    }
    /**
     * Create an entity
     * @method entity
     * @param  string               $class      the class name
     * @param  array                $key        the ID of the entity
     * @param  array|null           $data       optional data to populate with, if missing it is gathered from DB
     * @param  TableDefinition|null $definition optional explicit definition to use when creating the instance
     * @return mixed                            the instance
     */
    public function entity(string $class, array $key, array $data = null, TableDefinition $definition = null)
    {
        $class = $this->getClass($class);
        if (!$class) {
            $class = Row::CLASS;
        }
        if (!$definition) {
            if (!isset($this->classes[$class])) {
                throw new ORMException('No definition');
            }
            $definition = $this->classes[$class];
        }
        if (!isset($this->entities[$definition->getName()])) {
            $this->entities[$definition->getName()] = [];
        }
        $pk = [];
        foreach ($definition->getPrimaryKey() as $field) {
            $pk[$field] = $key[$field] ?? array_shift($key);
        }
        if (isset($this->entities[$definition->getName()][json_encode($pk)])) {
            return $this->entities[$definition->getName()][json_encode($pk)];
        }
        if ($data === null) {
            $table = new Query($this->db, $definition);
            foreach ($pk as $field => $value) {
                $table->filter($field, $value);
            }
            $data = $table->select();
            if (count($data) === 0) {
                throw new ORMException('Entry not found');
            }
            $data = $data[0];
        }
        $instance = call_user_func($this->creator, $class);
        if ($class === Row::CLASS) {
            $instance->__definition = $definition->getName();
        }
        foreach ($definition->getColumns() as $column) {
            $instance->{$column} = $data[$column] ?? null;
        }
        foreach ($definition->getRelations() as $name => $relation) {
            $query = new Query($this->db, $relation['table']);
            if ($relation['sql']) {
                $query->where($relation['sql'], $relation['par']);
            }
            if ($relation['pivot']) {
                $nm = null;
                foreach ($relation['table']->getRelations() as $rname => $rdata) {
                    if ($rdata['pivot'] && $rdata['pivot']->getName() === $relation['pivot']->getName()) {
                        $nm = $rname;
                    }
                }
                if (!$nm) {
                    $nm = $definition->getName();
                    $relation['table']->manyToMany(
                        $definition,
                        $relation['pivot'],
                        $nm,
                        array_flip($relation['keymap']),
                        $relation['pivot_keymap']
                    );
                }
                foreach ($pk as $k => $v) {
                    $query->filter($nm . '.' . $k, $v ?? null);
                }
            } else {
                foreach ($relation['keymap'] as $k => $v) {
                    $query->filter($v, $data[$k] ?? null);
                }
            }
            if ($relation['many'] === false && isset($data[$name])) {
                $data[$name] = [$data[$name]];
            }
            $instance->{$name} = new Collection($query, $this, $class, $data[$name] ?? null, $relation['many'] === false);
        }
        return $this->entities[$definition->getName()][json_encode($pk)] = $instance;
    }

    /**
     * Persist an instance to DB
     * @method save
     * @param  mixed $entity the instance object
     * @return array         the instance's primary key
     */
    public function save($entity) : array
    {
        $class = get_class($entity);
        $class = $this->getClass($class);
        $definition = null;
        if (!$class) {
            $class = Row::CLASS;
            if ($entity->__definition === null) {
                throw new ORMException('No definition');
            }
            $definition = $this->definitions[$entity->__definition];
        }
        if (!$definition) {
            if (!isset($this->classes[$class])) {
                throw new ORMException('No definition');
            }
            $definition = $this->classes[$class];
        }

        $data = [];
        foreach ($definition->getColumns() as $column) {
            $data[$column] = $entity->{$column} ?? null;
        }

        // primary keys
        if (!isset($this->entities[$definition->getName()])) {
            $this->entities[$definition->getName()] = [];
        }
        $old = array_search($entity, $this->entities[$definition->getName()], true);
        if ($old !== false) {
            $old = json_decode($old, true);
        }
        $new = [];
        foreach ($definition->getPrimaryKey() as $field) {
            $new[$field] = $entity->{$field};
        }

        // gather values from relations and set local fields
        foreach ($definition->getRelations() as $name => $relation) {
            if (count(array_diff(array_keys($relation['keymap']), array_keys($new)))) {
                $obj = $entity->{$name}[0];
                foreach ($relation['keymap'] as $local => $remote) {
                    $data[$local] = $obj->{$remote};
                }
            }
        }
        
        $q = new Query($this->db, $definition);
        if ($old === false) {
            $id = $q->insert($data);
            if ($id !== null && count($definition->getPrimaryKey()) === 1) {
                $field = current($definition->getPrimaryKey());
                $entity->{$field} = $id;
                $new[$field] = $id;
            }
            $this->entities[$definition->getName()][json_encode($new)] = $entity;
        } else {
            foreach ($old as $k => $v) {
                $q->filter($k, $v);
            }
            $q->update($data);
            if (json_encode($new) !== json_encode($old)) {
                unset($this->entities[$definition->getName()][json_encode($old)]);
                $this->entities[$definition->getName()][json_encode($new)] = $entity;
            }
        }

        foreach ($definition->getRelations() as $name => $relation) {
            if (!count(array_diff(array_keys($relation['keymap']), array_keys($new)))) {
                if (!$relation['pivot']) {
                    if ($old === false || json_encode($new) !== json_encode($old)) { // only on new ID
                        if (is_array($entity->{$name}) || $entity->{$name} instanceof \Traversable) {
                            foreach ($entity->{$name} as $obj) {
                                foreach ($relation['keymap'] as $local => $remote) {
                                    $obj->{$remote} = $new[$local];
                                }
                            }
                        }
                        if ($old !== false) {
                            $query = new Query($this->db, $relation['table']);
                            $data = [];
                            foreach ($relation['keymap'] as $local => $remote) {
                                $query->filter($remote, $old[$local]);
                                $data[$remote] = $new[$local];
                            }
                            $query->update($data);
                        }
                    }
                } else {
                    $query = new Query($this->db, $relation['pivot']);
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
                }
            }
        }
        return $new;
    }
    /**
     * Remove an instance from DB
     * @method delete
     * @param  mixed $entity the instance to remove
     * @return int           the deleted rows count
     */
    public function delete($entity) : int
    {
        $class = get_class($entity);
        $class = $this->getClass($class);
        if (!$class) {
            $class = Row::CLASS;
            $definition = $this->definitions[$entity->__definition];
        }
        if (!$definition) {
            if (!isset($this->classes[$class])) {
                throw new ORMException('No definition');
            }
            $definition = $this->classes[$class];
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

        $q = new Query($this->db, $definition);
        foreach ($pk as $field => $value) {
            $q->filter($field, $value);
        }
        $res = $q->delete();
        // delete relations (might not be necessary - FK may have already deleted those)
        foreach ($definition->getRelations() as $name => $relation) {
            if ($relation['pivot']) {
                $query = new Query($this->db, $relation['pivot']);
                foreach ($relation['keymap'] as $local => $remote) {
                    $query->filter($remote, $pk[$local]);
                }
                $query->delete();
            } else {
                if (!count(array_diff(array_keys($relation['keymap']), array_keys($pk)))) {
                    $query = new Query($this->db, $relation['table']);
                    if ($relation['sql']) {
                        $query->where($relation['sql'], $relation['par']);
                    }
                    foreach ($relation['keymap'] as $local => $remote) {
                        $query->filter($remote, $pk[$local]);
                    }
                    foreach ($query->select($relation['table']->getPrimaryKey()) as $row) {
                        $key = [];
                        foreach ($relation['table']->getPrimaryKey() as $field) {
                            $key[$field] = $row[$field] ?? null;
                        }
                        if (isset($this->entities[$relation['table']->getName()]) &&
                            isset($this->entities[$relation['table']->getName()][json_encode($key)])
                        ) {
                            unset($this->entities[$relation['table']->getName()][json_encode($key)]);
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
        }
        return $res;
    }
}
