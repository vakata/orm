<?php
namespace vakata\orm;

use vakata\database\DBInterface;

/**
 * A generic class mapping an instance creation function to a table in the DB.
 */
// NOTICE: if not using Unit of Work toArray() and updatePivots() may fail if saving is not in the right order!!!
// TODO: lazy relations are needed in entity()
class GenericDataMapper implements DataMapper
{
    protected $manager;
    protected $db;
    protected $table;
    protected $create;
    protected $definition;
    protected $map = [];
    
    /**
     * Create an instance
     *
     * @param Manager $manager the manager object
     * @param DBInterface $db the database access object
     * @param string $table the table name to query
     * @param callable $create invoked with an array of fields when a new instance needs to be created
     */
    public function __construct(Manager $manager, DBInterface $db, string $table, callable $create) {
        $this->manager = $manager;
        $this->db = $db;
        $this->table = $table;
        $this->create = $create;
        $this->definition = $this->db->table($table)->getDefinition();
    }

    // READ METHODS
    protected function hash($data) : string
    {
        if (is_object($data)) {
            $data = $this->toArray($data, false);
        }
        $pkey = [];
        foreach ($this->definition->getPrimaryKey() as $field) {
            $pkey[$field] = $data[$field] ?? null;
        }
        return json_encode($pkey, JSON_UNESCAPED_UNICODE);
    }
    protected function instance(array $data = [])
    {
        return call_user_func($this->create, $data);
    }
    protected function populate($entity, array $data = [])
    {
        // populate basic columns
        foreach ($data as $field => $value) {
            try {
                $method = 'set' . ucfirst(strtolower($field));
                if (method_exists($entity, $method)) {
                    $entity->{$method}($value);
                } else {
                    $entity->{$field} = $value;
                }
            } catch (\Exception $ignore) {}
        }
    }
    protected function populateRelations($entity, array $data = [])
    {
        foreach ($this->definition->getRelations() as $name => $relation) {
            if (isset($data[$name])) {
                $mapper = $this->manager->getMapper($relation->table->getName());
                $entity->{$name} = $relation->many ? 
                    array_map(function ($v) use ($mapper) {
                        return $mapper->entity($v);
                    }, $data[$name]) :
                    $mapper->entity($data[$name]);
            } else {
                $query = $this->db->table($relation->table->getName());
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
                    foreach ($this->definition->getPrimaryKey() as $v) {
                        $query->filter($nm . '.' . $v, $data[$v] ?? null);
                    }
                } else {
                    foreach ($relation->keymap as $k => $v) {
                        $query->filter($v, $data[$k] ?? null);
                    }
                }
                $query = $this->manager->fromQuery($query);
                if ($relation->many) {
                    $entity->{$name} = $query;
                } else {
                    // TODO: lazy property
                    $entity->{$name} = $query[0];
                }
            }
        }
    }
    /**
     * Convert an entity to an array of fields, optionally including relation fields. 
     *
     * @param mixed $entity the entity to convert
     * @param bool $relations should the 1 end of relations be included, defaults to `true`
     * @return array
     */
    public function toArray($entity, bool $relations = true) : array
    {
        $data = [];
        foreach ($this->definition->getColumns() as $column) {
            $method = 'get' . ucfirst(strtolower($column));
            if (method_exists($entity, $method)) {
                $data[$column] = $entity->{$method}();
            } else {
                try {
                    $data[$column] = $entity->{$column};
                } catch (\Exception $ignore) {}
            }
        }
        // gather data from relations
        if ($relations) {
            foreach ($this->definition->getRelations() as $name => $relation) {
                if ($relation->many) {
                    continue;
                }
                $value = null;
                $method = 'get' . ucfirst(strtolower($name));
                if (method_exists($entity, $method)) {
                    $value = $entity->{$method}();
                } else {
                    try {
                        $value = $entity->{$name};
                    } catch (\Exception $ignore) {
                        continue;
                    }
                }
                $pkfields = $this->definition->getPrimaryKey();
                foreach ($relation->keymap as $local => $remote) {
                    if (!in_array($local, $pkfields)) {
                        $data[$local] = null;
                        $method = 'get' . ucfirst(strtolower($remote));
                        if (is_object($value)) {
                            if (method_exists($value, $method)) {
                                $data[$local] = $value->{$method}();
                            } else {
                                try {
                                    $data[$local] = $value->{$remote};
                                } catch (\Exception $ignore) {}
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }
    /**
     * Get an entity from an array of fields
     *
     * @param array $row
     * @return mixed
     */
    public function entity(array $row)
    {
        // create a primary key hash
        $hash = $this->hash($row);
        if (isset($this->map[$hash])) {
            return $this->map[$hash];
        }
        // create an instance
        $entity = $this->instance($row);
        // populate basic fields
        $this->populate($entity, $row);
        // populate relations
        $this->populateRelations($entity, $row);
        // return entity (and save in map)
        return $this->map[$hash] = $entity;
    }

    // WRITE METHODS
    protected function updateRelations($entity, array $pkey)
    {
        foreach ($this->definition->getRelations() as $name => $relation) {
            if ($relation->pivot) {
                continue;
            }
            // only relations like book (with author_id)
            if (!count(array_diff(array_keys($relation->keymap), array_keys($pkey)))) {
                $data = null;
                $method = 'get' . ucfirst(strtolower($name));
                if (method_exists($entity, $method)) {
                    $data = $entity->{$method}();
                } else {
                    try {
                        $data = $entity->{$name};
                    } catch (\Exception $e) {}
                }
                if (!isset($data)) {
                    continue;
                }
                $data = $relation->many ? $data : [ $data ];
                foreach ($data as $item) {
                    foreach ($relation->keymap as $local => $remote) {
                        if (isset($pkey[$local])) {
                            try {
                                $method = 'set' . ucfirst(strtolower($remote));
                                if (method_exists($item, $method)) {
                                    $item->{$method}($pkey[$local]);
                                } else {
                                    $item->{$remote} = $pkey[$local];
                                }
                            } catch (\Exception $ignore) {}
                        }
                    }
                }
            }
        }
    }
    protected function updatePivots($entity, array $pkey, bool $force = false)
    {
        foreach ($this->definition->getRelations() as $name => $relation) {
            if (!$relation->pivot) {
                continue;
            }
            $data = null;
            $method = 'get' . ucfirst(strtolower($name));
            if (method_exists($entity, $method)) {
                $data = $entity->{$method}();
            } else {
                try {
                    $data = $entity->{$name};
                } catch (\Exception $e) {}
            }
            if (!isset($data)) {
                continue;
            }
            if ($force || !($data instanceof Repository) || $data->isModified()) {
                $query = $this->db->table($relation->pivot->getName());
                foreach ($relation->keymap as $local => $remote) {
                    $query->filter($remote, $pkey[$local]);
                }
                $query->delete();
                $insert = [];
                foreach ($relation->keymap as $local => $remote) {
                    $insert[$remote] = $pkey[$local];
                }
                foreach ($data as $item) {
                    $query->reset();
                    foreach ($relation->pivot_keymap as $local => $remote) {
                        $insert[$local] = null;
                        $method = 'get' . ucfirst(strtolower($remote));
                        if (method_exists($item, $method)) {
                            $insert[$local] = $item->{$method}();
                        } else {
                            try {
                                $insert[$local] = $item->{$remote};
                            } catch (\Exception $e) {}
                        }
                    }
                    $query->insert($insert);
                }
            }
        }
    }
    protected function deleteRelations($entity, array $pkey)
    {
        foreach ($this->definition->getRelations() as $name => $relation) {
            if (!count(array_diff(array_keys($relation->keymap), array_keys($pkey)))) {
                if ($relation->pivot) {
                    $query = $this->db->table($relation->pivot->getName());
                    foreach ($relation->keymap as $local => $remote) {
                        $query->filter($remote, $pkey[$local] ?? null);
                    }
                    $query->delete();
                } else {
                    $data = null;
                    $method = 'get' . ucfirst(strtolower($name));
                    if (method_exists($entity, $method)) {
                        $data = $entity->{$method}();
                    } else {
                        try {
                            $data = $entity->{$name};
                        } catch (\Exception $ignore) {}
                    }
                    if (!isset($data)) {
                        continue;
                    }
                    $repository = $this->manager->fromTable(
                        $relation->pivot ? $relation->pivot->getName() : $relation->table->getName()
                    );
                    $data = $relation->many ? $data : [ $data ];
                    foreach ($data as $item) {
                        $repository->remove($item);
                    }
                }
            }
        }
    }
    /**
     * Insert an entity, returning the primary key fields and their value
     *
     * @param mixed $entity
     * @return array a key value map of the primary key columns
     */
    public function insert($entity) : array
    {
        $data = $this->toArray($entity);
        $pkey = $this->db->table($this->table)->insert($data);
        $this->populate($entity, $pkey);
        $this->map[$this->hash($entity)] = $entity;
        $this->updateRelations($entity, $pkey);
        $this->updatePivots($entity, $pkey);
        return $pkey;
    }
    /**
     * Update an entity
     *
     * @param mixed $entity
     * @return int the number of affected rows
     */
    public function update($entity) : int
    {
        // get the current primary key
        $hash = array_search($entity, $this->map, true);
        if ($hash === null) {
            $hash = $this->hash($entity);
            $this->map[$hash] = $entity;
        }
        $pkey = json_decode($hash, true);
        // create a query and filter to match primary key
        $query = $this->db->table($this->table);
        foreach ($this->definition->getPrimaryKey() as $field) {
            $query->filter($field, $pkey[$field] ?? null);
        }
        $data = $this->toArray($entity);
        $updatedCount = $query->update($data);
        // check for primary key changes
        $newHash = $this->hash($entity);
        if ($hash !== $newHash) {
            unset($this->map[$hash]);
            $this->map[$newHash] = $entity;
            $this->updateRelations($entity, json_decode($newHash, true));
        }
        $this->updatePivots($entity, json_decode($newHash, true), $hash !== $newHash);
        return $updatedCount;
    }
    /**
     * Delete an entity
     *
     * @param mixed $entity
     * @return int the number of deleted rows
     */
    public function delete($entity) : int
    {
        // get current primary key
        $hash = array_search($entity, $this->map, true);
        if ($hash === null) {
            $hash = $this->hash($entity);
            $this->map[$hash] = $entity;
        }
        $pkey = json_decode($hash, true);
        // create a query and filter to match primary key
        $query = $this->db->table($this->table);
        foreach ($this->definition->getPrimaryKey() as $field) {
            $query->filter($field, $pkey[$field] ?? null);
        }
        $deletedCount = $query->delete();
        unset($this->map[$hash]);
        // delete data in related tables (probably not needed with cascade FK)
        $this->deleteRelations($entity, $pkey);
        return $deletedCount;
    }
}
