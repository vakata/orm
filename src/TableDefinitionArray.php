<?php

namespace vakata\orm;

class TableDefinitionArray implements TableDefinitionInterface, \JsonSerializable
{
    protected $definition = [];

    public function __construct($table, array $definition = [])
    {
        $this->definition = array_merge(
            ['name' => $table, 'primary_key' => [], 'columns' => [], 'definitions' => [], 'indexed'=>[]],
            $definition
        );
    }
    public function __get($key)
    {
        if (isset($this->definition[$key])) {
            return $this->definition[$key];
        }
        if (method_exists($this, 'get'.ucfirst($key))) {
            return call_user_func([$this, 'get'.ucfirst($key)]);
        }

        return;
    }

    public function getName()
    {
        return $this->definition['name'];
    }
    public function getPrimaryKey()
    {
        return $this->definition['primary_key'];
    }
    public function getColumns()
    {
        return $this->definition['columns'];
    }

    public function toArray()
    {
        return $this->definition;
    }
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
