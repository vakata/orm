<?php

namespace vakata\orm;

class TableRow implements TableRowInterface
{
    protected $data = [];
    protected $chng = [];
    protected $relations = [];

    public function __construct(array $data = [], array $relations = [])
    {
        $this->data = $data;
        $this->relations = $relations;
    }
    public function __get($key)
    {
        if (isset($this->chng[$key])) {
            return $this->chng[$key];
        }
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        if (isset($this->relations[$key])) {
            return $this->relations[$key]->many ? $this->relations[$key] : ($this->relations[$key][0] ?? null);
        }
        return null;
    }
    public function __set($key, $value)
    {
        if (isset($this->chng[$key]) || !isset($this->data[$key]) || $this->data[$key] !== $value) {
            $this->chng[$key] = $value;
        }
        if (isset($this->relations[$key])) {
            foreach ($this->relations[$key] as $k => $v) {
                unset($this->relations[$key][$k]);
            }
            if ($value !== null) {
                $this->relations[$key][] = $value;
            }
        }
    }
    public function __call($key, $args)
    {
        if (!isset($this->relations[$key])) {
            return null;
        }
        return $this->relations[$key];
    }

    public function toArray($full = true)
    {
        $temp = array_merge($this->data, $this->chng);
        if ($full) {
            foreach ($this->relations as $k => $v) {
                $temp[$k] = $v->toArray(true);
            }
        }
        return $temp;
    }
}