<?php

namespace vakata\orm;

interface LazyLoadable
{
    public function lazyProperty(string $property, callable $resolve);
    public function loadLazyProperty(string $property);
    public function overrideLazyProperty(string $property, $value);
}