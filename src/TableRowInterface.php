<?php

namespace vakata\orm;

interface TableRowInterface
{
    public function toArray($full = true);
}
