<?php

namespace vakata\orm;

interface TableInterface extends \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
    public function getDatabase();
    public function getDefinition();

    public function getRelations();
    public function getRelationKeys();

    public function hasOne($to_table, $name = null, $to_table_column = null);
    public function hasMany($to_table, $name = null, $to_table_column = null);
    public function belongsTo($to_table, $name = null, $to_table_column = null);
    public function manyToMany($to_table, $pivot, $name = null, $to_table_column = null, $local_column = null);

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
