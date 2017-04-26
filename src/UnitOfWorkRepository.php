<?php
namespace vakata\orm;

class UnitOfWorkRepository implements SearchableRepository
{
    protected $uow;
    protected $repository;

    public function __construct(Repository $repository, UnitOfWork $uow)
    {
        $this->repository = $repository;
        $this->uow = $uow;
    }

    public function find($id)
    {
        return $this->repository->find($id);
    }
    public function filter(string $column, $value) : Repository
    {
        $this->repository->filter($column, $value);
        return $this;
    }
    public function sort(string $column, bool $desc = false) : Repository
    {
        $this->repository->sort($column, $desc);
        return $this;
    }
    public function limit(int $limit, int $offset = 0) : Repository
    {
        $this->repository->limit($limit, $offset);
        return $this;
    }
    public function reset() : Repository
    {
        $this->repository->reset();
        return $this;
    }

    public function add($entity) : Repository
    {
        $this->uow->add($entity, $this->repository->getMapper());
        return $this;
    }
    public function remove($entity) : Repository
    {
        $this->uow->remove($entity, $this->repository->getMapper());
        return $this;
    }

    public function current()
    {
        $data = $this->repository->current();
        if ($data !== null) {
            $this->uow->register($data, $this->repository->getMapper());
        }
        return $data;
    }
    public function offsetGet($offset)
    {
        $data = $this->repository->offsetGet($offset);
        if ($data !== null) {
            $this->uow->register($data, $this->repository->getMapper());
        }
        return $data;
    }

    public function key()
    {
        return $this->repository->key();
    }
    public function rewind()
    {
        return $this->repository->rewind();
    }
    public function next()
    {
        return $this->repository->next();
    }
    public function valid()
    {
        return $this->repository->valid();
    }
    public function offsetExists($offset)
    {
        return $this->repository->offsetExists($offset);
    }
    public function offsetUnset($offset)
    {
        return $this->repository->offsetUnset($offset);
    }
    public function offsetSet($offset, $value)
    {
        return $this->repository->offsetSet($offset, $value);
    }
    public function count()
    {
        return $this->repository->count();
    }

    public function getMapper() : DataMapper
    {
        return $this->repository->getMapper();
    }
    public function isConsumed() : bool
    {
        return $this->consumed;
    }
    public function isModified() : bool
    {
        return $this->modified;
    }
    public function search(string $q) : SearchableRepository
    {
        if (!($this->repository instanceof SearchableRepository)) {
            throw new \BadMethodCallException();
        }
        return $this->repository->search($q);
    }
}