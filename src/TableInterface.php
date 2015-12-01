<?php

namespace vakata\orm;

interface TableInterface extends \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
    public function getDatabase();
    public function getDefinition();

    public function getRelations();
    public function getRelationKeys();

    public function hasOne($toTable, $name = null, $toTableColumn = null);
    public function hasMany($toTable, $name = null, $toTableColumn = null);
    public function belongsTo($toTable, $name = null, $toTableColumn = null);
    public function manyToMany($toTable, $pivot, $name = null, $toTableColumn = null, $local_column = null);

    public function select($limit = 0, $offset = 0, array $fields = null);
    public function where($sql, array $params = []);
    public function order($order, $raw = false);

    public function filter($column, $value);

    public function count();
    public function reset();

    public function toArray($full = true);

    public function create(array $data = []);
    public function save(array $data = []);
    public function delete();
}
