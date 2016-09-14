<?php
namespace vakata\orm;

/**
 * A dummy class used when hitting tables with no defined classes
 */
class Row
{
    protected $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }
    public function __get($key)
    {
        return $this->data[$key] ?? null;
    }
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }
}
