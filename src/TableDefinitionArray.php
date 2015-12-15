<?php

namespace vakata\orm;

/**
 * TableDefinitionArray is used when working with the \vakata\orm\Table class.
 * The class provides information about a table in the database.
 * Data is not autocollected (as with \vakata\orm\TableDefinition) - the class relies on data that is passed in.
 */
class TableDefinitionArray implements TableDefinitionInterface, \JsonSerializable
{
    protected $definition = [];
    /**
     * Create an instance.
     * @method __construct
     * @param  string      $table      the table name
     * @param  array       $definition the table definition (array with at least "primary_key" and "columns" keys)
     */
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
    /**
     * Get the table name.
     * @method getName
     * @return string  the name of the table
     */
    public function getName()
    {
        return $this->definition['name'];
    }
    /**
     * Get the columns forming the primary key.
     * @method getPrimaryKey
     * @return array        array of primary key columns
     */
    public function getPrimaryKey()
    {
        return $this->definition['primary_key'];
    }
    /**
     * Get a list of columns.
     * @method getColumns
     * @return array     array of strings of column names
     */
    public function getColumns()
    {
        return $this->definition['columns'];
    }
    /**
     * Get the current definition as an array.
     * @method toArray
     * @return array  the definition
     */
    public function toArray()
    {
        return $this->definition;
    }
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
