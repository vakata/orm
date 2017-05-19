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
    public function search(string $q) : Repository;

    public function append($entity) : Repository;
    public function change($entity) : Repository;
    public function remove($entity) : Repository;

    public function toArray($entity) : array;
    public function isConsumed() : bool;
    public function isModified() : bool;
}