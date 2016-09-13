<?php
namespace vakata\orm;

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
