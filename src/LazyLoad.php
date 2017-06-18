<?php

namespace vakata\orm;

trait LazyLoad
{
    protected $lazyProperties = [];
    public function lazyProperty(string $property, callable $resolve)
    {
        unset($this->{$property});
        $this->lazyProperties[$property] = [ 'loaded' => false, 'value' => null, 'resolve' => $resolve ];
        return $this;
    }
    public function overrideLazyProperty(string $property, $value)
    {
        if (!isset($this->lazyProperties[$property])) {
            return $this;
        }
        $this->lazyProperties[$property]['value'] = $value;
        $this->lazyProperties[$property]['loaded'] = true;
        $this->{$property} = $this->lazyProperties[$property]['value'];
        return $this;
    }
    public function loadLazyProperty(string $property)
    {
        if (!isset($this->lazyProperties[$property])) {
            throw new \Exception('Lazy property not found');
        }
        if ($this->lazyProperties[$property]['loaded']) {
            return $this->lazyProperties[$property]['value'];
        }
        $this->lazyProperties[$property]['value'] = call_user_func($this->lazyProperties[$property]['resolve']);
        $this->lazyProperties[$property]['loaded'] = true;
        return $this->{$property} = $this->lazyProperties[$property]['value'];
    }
    public function __get($property)
    {
        if (isset($this->lazyProperties[$property])) {
            return $this->loadLazyProperty($property);
        }
        return null;
    }
}