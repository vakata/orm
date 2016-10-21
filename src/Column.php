<?php
namespace vakata\orm;

use vakata\database\DatabaseInterface;

/**
 * A column definition
 */
class Column
{
    protected $name;
    protected $type;
    protected $btype = 'text';
    protected $values = [];
    protected $default = null;
    protected $comment = null;
    protected $nullable = false;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    public static function fromArray(string $name, array $data = [])
    {
        $instance = new static($name);
        if (isset($data['Type'])) {
            $instance->setType($data['Type']);
        }
        if (isset($data['type'])) {
            $instance->setType($data['type']);
        }
        if (isset($data['Comment'])) {
            $instance->setComment($data['Comment']);
        }
        if (isset($data['comment'])) {
            $instance->setComment($data['comment']);
        }
        if (isset($data['Null']) && $data['Null'] === 'YES') {
            $instance->setNullable(true);
        }
        if (isset($data['nullable']) && is_bool($data['nullable'])) {
            $instance->setNullable($data['nullable']);
        }
        if (isset($data['Default'])) {
            $instance->setDefault($data['Default']);
        }
        if (isset($data['default'])) {
            $instance->setDefault($data['default']);
        }
        if ($instance->getBasicType() === 'enum' && strpos($instance->getType(), 'enum(') === 0) {
            $temp = array_map(function ($v) {
                return str_replace("''", "'", $v);
            }, explode("','", substr($instance->getType(), 6, -2)));
            $instance->setValues($temp);
        }
        if (isset($data['values']) && is_array($data['values'])) {
            $instance->setValues($data['values']);
        }
        if (isset($data['DATA_TYPE'])) {
            $instance->setType($data['DATA_TYPE']);
        }
        if (isset($data['NULLABLE']) && $data['NULLABLE'] !== 'N') {
            $instance->setNullable(true);
        }
        if (isset($data['DATA_DEFAULT'])) {
            $instance->setDefault($data['DATA_DEFAULT']);
        }
        return $instance;
    }

    public function getName()
    {
        return $this->name;
    }
    public function getType()
    {
        return $this->type;
    }
    public function getValues()
    {
        return $this->values;
    }
    public function getDefault()
    {
        return $this->default;
    }
    public function isNullable()
    {
        return $this->nullable;
    }
    public function getComment()
    {
        return $this->comment;
    }
    public function getBasicType()
    {
        return $this->btype;
    }
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }
    public function setType(string $type)
    {
        $this->type = $type;
        $type = strtolower($type);
        if (strpos($type, 'text') !== false || strpos($type, 'char') !== false) {
            $this->btype = 'text';
        } elseif (strpos($type, 'int') !== false || strpos($type, 'bit') !== false) {
            $this->btype = 'int';
        } elseif (strpos($type, 'float') !== false || strpos($type, 'double') !== false || strpos($type, 'decimal') !== false) {
            $this->btype = 'float';
        } elseif (strpos($type, 'enum') !== false || strpos($type, 'set') !== false) {
            $this->btype = 'enum';
        } elseif (strpos($type, 'datetime') !== false || strpos($type, 'timestamp') !== false) {
            $this->btype = 'datetime';
        } elseif (strpos($type, 'date') !== false) {
            $this->btype = 'date';
        } elseif (strpos($type, 'lob') !== false || strpos($type, 'binary') !== false) {
            $this->btype = 'blob';
        }
        return $this;
    }
    public function setValues(array $values)
    {
        $this->values = $values;
        return $this;
    }
    public function setDefault($default = null)
    {
        $this->default = $default;
        return $this;
    }
    public function setNullable(bool $nullable)
    {
        $this->nullable = $nullable;
        return $this;
    }
    public function setComment(string $comment)
    {
        $this->comment = $comment;
        return $this;
    }
}