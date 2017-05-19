<?php
namespace vakata\orm;

use vakata\database\DBInterface;

class UnitOfWork
{
    const CLEAN   = 0;
    const DIRTY   = 1;
    const ADDED   = 2;
    const REMOVED = 3;

    /**
     * @var DBInterface
     */
    protected $db;
    /**
     * @var array
     */
    protected $map = [];

    /**
     * Create an instance
     *
     * @param DBInterface $db the database object (a transaction will be used when calling save)
     */
    public function __construct(DBInterface $db)
    {
        $this->db = $db;
    }

    protected function hash($entity)
    {
        return \spl_object_hash($entity);
    }
    protected function detectChanges()
    {
        foreach ($this->map as $hash => $data) {
            if ($data['state'] === static::CLEAN &&
                $data['hash']  !== sha1(serialize($data['repository']->toArray($data['entity']))) // TODO
            ) {
                $this->map[$hash]['state'] = static::DIRTY;
            }
        }
    }
    protected function isDirty()
    {
        $this->detectChanges();
        foreach ($this->map as $hash => $data) {
            if ($this->map[$hash]['state'] !== static::CLEAN) {
                return true;
            }
        }
        return false;
    }
    /**
     * Register an entity in the unit (will be checked for changes when save is called)
     *
     * @param mixed $entity
     * @param Repository $repository the entity's repository
     * @return string the entity's hash
     */
    public function register($entity, Repository $repository)
    {
        $hash = $this->hash($entity);
        if (!isset($this->map[$hash])) {
            $this->map[$hash] = [
                'hash'       => sha1(serialize($repository->toArray($entity))),
                'state'      => static::CLEAN,
                'entity'     => $entity,
                'repository' => $repository
            ];
        }
        return $hash;
    }
    /**
     * Mark an entity as changed
     *
     * @param mixed $entity
     * @param Repository $repository the entity's repository
     * @return string the entity's hash
     */
    public function change($entity, Repository $repository)
    {
        $hash = $this->register($entity, $repository);
        $this->map[$hash]['state'] = static::DIRTY;
        return $hash;
    }
    /**
     * Add a new entity (mark for insertion)
     *
     * @param mixed $entity
     * @param Repository $repository the entity's repository
     * @return string the entity's hash
     */
    public function append($entity, Repository $repository)
    {
        $hash = $this->register($entity, $repository);
        $this->map[$hash]['state'] = static::ADDED;
        return $hash;
    }
    /**
     * Mark an entity for removal
     *
     * @param mixed $entity
     * @param Repository $repository the entity's repository
     * @return string the entity's hash
     */
    public function remove($entity, Repository $repository)
    {
        $hash = $this->register($entity, $repository);
        $this->map[$hash]['state'] = static::REMOVED;
        return $hash;
    }
    /**
     * Persist all changes
     *
     * @return void
     */
    public function save()
    {
        $this->db->begin();
        try {
            while ($this->isDirty()) {
                foreach ($this->map as $hash => $data) {
                    if ($data['state'] === static::ADDED) {
                        $data['repository']->append($data['entity']);
                        $this->map[$hash]['hash'] = sha1(serialize($data['repository']->toArray($data['entity'])));
                        $this->map[$hash]['state'] = static::CLEAN;
                    }
                }
                foreach ($this->map as $hash => $data) {
                    if ($data['state'] === static::DIRTY) {
                        $data['repository']->change($data['entity']);
                        $this->map[$hash]['hash'] = sha1(serialize($data['repository']->toArray($data['entity'])));
                        $this->map[$hash]['state'] = static::CLEAN;
                    }
                }
                foreach ($this->map as $hash => $data) {
                    if ($data['state'] === static::REMOVED) {
                        $data['repository']->remove($data['entity']);
                        unset($this->map[$hash]);
                    }
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}