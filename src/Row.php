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
        $this->data = [];
        foreach ($data as $k => $v) {
            $this->data[strtolower($k)] = $v;
        }
    }
    public function __get($key)
    {
        return $this->data[strtolower($key)] ?? null;
    }
    public function __set($key, $value)
    {
        $this->data[strtolower($key)] = $value;
    }
}
