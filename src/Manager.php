<?php
namespace vakata\orm;

use vakata\database\DatabaseInterface;

class Manager
{
    protected $db;
    protected $classes = [];
    protected $entities = [];
    protected $definitions = [];

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function addDefinition(TableDefinition $definition)
    {
        if (!isset($this->definitions[$definition->getName()])) {
            $this->definitions[$definition->getName()] = $definition;
        }
        return $this;
    }
    public function addDefinitionByTableName(string $table)
    {
        if (!isset($this->definitions[$table])) {
            $this->addDefinition(TableDefinition::fromDatabase($this->db, $table));
        }
        return $this;
    }
    public function getDefinition(string $search)
    {
        return $this->definitions[$search] ?? null;
    }
    public function addClass(string $class, TableDefinition $definition)
    {
        if (!isset($this->definitions[$table->getName()])) {
            $this->addDefinition($definition);
        }
        $this->classes[$class] = $this->definitions[$definition->getName()];
        return $this;
    }
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
        // TODO: add DIContainer here? or callback?
        $instance = new $class();
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
                    //$relation['table']->hasRelation($definition->getName())) {
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

    public function save($entity)
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

        $data = [];
        foreach ($definition->getColumns() as $column) {
            $data[$column] = $entity->{$column} ?? null;
        }

        // primary keys
        if (!isset($this->entities[$definition->getName()])) {
            $this->entities[$definition->getName()] = [];
        }
        $org = array_search($entity, $this->entities[$definition->getName()], true);
        if ($org !== false) {
            $org = json_decode($orig, true);
        }
        $new = [];
        foreach ($definition->getPrimaryKey() as $field) {
            $new[$field] = $entity->{$field};
        }

        // gather values from relations and set local fields
        foreach ($definition->getRelations() as $name => $relation) {
            if (count(array_diff(array_keys($relation['keymap']), array_keys($pk)))) {
                $obj = $entity->{$name}[0]; // maybe save? it could be a new object?
                foreach ($relation['keymap'] as $local => $remote) {
                    $data[$local] = $obj->{$remote};
                }
            }
        }
        
        $q = new Query($this->db, $definition);
        if ($org === false) {
            $id = $q->insert($data);
            if ($id !== null && count($definition->getPrimaryKey()) === 1) {
                $field = current($definition->getPrimaryKey());
                $entity->{$field} = $id;
                $new[$field] = $id;
            }
            $this->entities[$definition->getName()][json_encode($new)] = $entity;
            // TODO: update related objects with the new ID and relation (do not save them), 
            // nothing to update in DB - its a new record

        } else {
            foreach ($org as $k => $v) {
                $q->where($k, $v);
            }
            $q->update($data);
            if (json_encode($new) !== json_encode($org)) {
                unset($this->entities[$definition->getName()][json_encode($org)]);
                $this->entities[$definition->getName()][json_encode($new)] = $entity;
                // TODO: update related objects with the new ID and relation and hit the DB?
            }
        }
        // TODO: gather all pivoted relations and write to pivot table
        // first drop all rows then write the new ones
        return $new;
    }
    public function delete($entity)
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