<?php
namespace vakata\orm;

interface Repository extends \Iterator, \ArrayAccess, \Countable
{
    public function find($id);
    public function filter(string $column, $value) : Repository;
    public function reject(string $column, $value) : Repository;
    public function sort(string $column, bool $desc = false) : Repository;
    public function limit(int $limit, int $offset = 0) : Repository;
    public function reset() : Repository;

    public function add($entity) : Repository;
    public function remove($entity) : Repository;

    public function getMapper() : DataMapper;
    public function isConsumed() : bool;
    public function isModified() : bool;
}