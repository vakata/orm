<?php

namespace vakata\orm;

interface TableInterface extends \Iterator, \ArrayAccess, \Countable
{
    public function getDefinition() : TableDefinition;

    public function filter(string $column, $value) : TableInterface;
    public function sort(string $column, bool $desc = false) : TableInterface;
    public function paginate(int $page = 1, int $perPage = 25) : TableInterface;
    public function reset() : TableInterface;

    public function where(string $sql, array $params = []) : TableInterface;
    public function order(string $sql, array $params = []) : TableInterface;
    public function limit(int $limit, int $offset = 0) : TableInterface;

    public function create(array $data = []) : TableRowInterface;
    public function save(TableRowInterface $row) : TableRowInterface;
    public function delete(TableRowInterface $row) : TableRowInterface;
}
