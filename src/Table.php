<?php
namespace vakata\orm;

use vakata\database\DatabaseInterface;

/**
 * A table definition
 */
class Table
{
    protected $data = [];
    protected $relations = [];

    /**
     * Create a new instance
     * @method __construct
     * @param  string      $name the table name
     */
    public function __construct(string $name)
    {
        $this->data = [
            'name'    => $name,
            'columns' => [],
            'primary' => [],
            'comment' => ''
        ];
        $this->relations = [];
    }
    /**
     * Get the table comment
     * @method getComment
     * @return string  the table comment
     */
    public function getComment()
    {
        return $this->data['comment'];
    }
    /**
     * Set the table comment
     * @method setComment
     * @param  string    $comment     the table comment
     * @return self
     */
    public function setComment(string $comment)
    {
        $this->data['comment'] = $comment;
        return $this;
    }
    /**
     * Add a column to the definition
     * @method addColumn
     * @param  string    $column     the column name
     * @param  array     $definition optional array of data associated with the column
     * @return  self
     */
    public function addColumn(string $column, array $definition = []) : Table
    {
        $this->data['columns'][$column] = Column::fromArray($column, $definition);
        return $this;
    }
    /**
     * Add columns to the definition
     * @method addColumns
     * @param  array      $columns key - value pairs, where each key is a column name and each value - array of info
     * @return  self
     */
    public function addColumns(array $columns) : Table
    {
        foreach ($columns as $column => $definition) {
            if (is_numeric($column) && is_string($definition)) {
                $this->addColumn($definition, []);
            } else {
                $this->addColumn($column, $definition);
            }
        }
        return $this;
    }
    /**
     * Set the primary key
     * @method setPrimaryKey
     * @param  array|string        $column either a single column name or an array of column names
     * @return  self
     */
    public function setPrimaryKey($column) : Table
    {
        if (!is_array($column)) {
            $column = [ $column ];
        }
        $this->data['primary'] = $column;
        return $this;
    }
    /**
     * Get the table name
     * @method getName
     * @return string  the table name
     */
    public function getName()
    {
        return $this->data['name'];
    }
    /**
     * Get a column definition
     * @method getColumn
     * @param  string    $column the column name to search for
     * @return array|null the column details or `null` if the column does not exist
     */
    public function getColumn($column)
    {
        return $this->data['columns'][$column] ?? null;
    }
    /**
     * Get all column names
     * @method getColumns
     * @return array     array of strings, where each element is a column name
     */
    public function getColumns()
    {
        return array_keys($this->data['columns']);
    }
    /**
     * Get all column definitions
     * @method getFullColumns
     * @return array         key - value pairs, where each key is a column name and each value - the column data
     */
    public function getFullColumns()
    {
        return $this->data['columns'];
    }
    /**
     * Get the primary key columns
     * @method getPrimaryKey
     * @return array        array of column names
     */
    public function getPrimaryKey()
    {
        return $this->data['primary'];
    }
    /**
     * Create a relation where each record has zero or one related rows in another table
     * @method hasOne
     * @param  Table   $toTable       the related table definition
     * @param  string|null       $name          the name of the relation (defaults to the related table name)
     * @param  string|array|null $toTableColumn the remote columns pointing to the PK in the current table
     * @param  string|null       $sql           additional where clauses to use, default to null
     * @param  array             $par           parameters for the above statement, defaults to an empty array
     * @return self
     */
    public function hasOne(
        Table $toTable,
        string $name = null,
        $toTableColumn = null,
        string $sql = null,
        array $par = []
    ) : Table {
        $columns = $toTable->getColumns();

        $keymap = [];
        if (!isset($toTableColumn)) {
            $toTableColumn = [];
        }
        if (!is_array($toTableColumn)) {
            $toTableColumn = [$toTableColumn];
        }
        foreach ($this->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($toTableColumn[$pkField])) {
                $key = $toTableColumn[$pkField];
            } elseif (isset($toTableColumn[$k])) {
                $key = $toTableColumn[$k];
            } else {
                $key = $this->getName().'_'.$pkField;
            }
            if (!in_array($key, $columns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $keymap[$pkField] = $key;
        }

        if (!isset($name)) {
            $name = $toTable->getName() . '_' . implode('_', array_keys($keymap));
        }
        $this->relations[$name] = [
            'name' => $name,
            'table' => $toTable,
            'keymap' => $keymap,
            'many' => false,
            'pivot' => null,
            'pivot_keymap' => [],
            'sql' => $sql,
            'par' => $par
        ];
        return $this;
    }
    /**
     * Create a relation where each record has zero, one or more related rows in another table
     * @method hasMany
     * @param  Table   $toTable       the related table definition
     * @param  string|null       $name          the name of the relation (defaults to the related table name)
     * @param  string|array|null $toTableColumn the remote columns pointing to the PK in the current table
     * @param  string|null       $sql           additional where clauses to use, default to null
     * @param  array             $par           parameters for the above statement, defaults to an empty array
     * @return self
     */
    public function hasMany(
        Table $toTable,
        string $name = null,
        $toTableColumn = null,
        $sql = null,
        array $par = []
    ) : Table {
        $columns = $toTable->getColumns();

        $keymap = [];
        if (!isset($toTableColumn)) {
            $toTableColumn = [];
        }
        if (!is_array($toTableColumn)) {
            $toTableColumn = [$toTableColumn];
        }
        foreach ($this->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($toTableColumn[$pkField])) {
                $key = $toTableColumn[$pkField];
            } elseif (isset($toTableColumn[$k])) {
                $key = $toTableColumn[$k];
            } else {
                $key = $this->getName().'_'.$pkField;
            }
            if (!in_array($key, $columns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $keymap[$pkField] = $key;
        }

        if (!isset($name)) {
            $name = $toTable->getName().'_'.implode('_', array_keys($keymap));
        }
        $this->relations[$name] = [
            'name' => $name,
            'table' => $toTable,
            'keymap' => $keymap,
            'many' => true,
            'pivot' => null,
            'pivot_keymap' => [],
            'sql' => $sql,
            'par' => $par
        ];
        return $this;
    }
    /**
     * Create a relation where each record belongs to another row in another table
     * @method belongsTo
     * @param  Table   $toTable       the related table definition
     * @param  string|null       $name          the name of the relation (defaults to the related table name)
     * @param  string|array|null $localColumn   the local columns pointing to the PK of the related table
     * @param  string|null       $sql           additional where clauses to use, default to null
     * @param  array             $par           parameters for the above statement, defaults to an empty array
     * @return self
     */
    public function belongsTo(
        Table $toTable,
        string $name = null,
        $localColumn = null,
        $sql = null,
        array $par = []
    ) : Table {
        $columns = $this->getColumns();

        $keymap = [];
        if (!isset($localColumn)) {
            $localColumn = [];
        }
        if (!is_array($localColumn)) {
            $localColumn = [$localColumn];
        }
        foreach ($toTable->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($localColumn[$pkField])) {
                $key = $localColumn[$pkField];
            } elseif (isset($localColumn[$k])) {
                $key = $localColumn[$k];
            } else {
                $key = $toTable->getName().'_'.$pkField;
            }
            if (!in_array($key, $columns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $keymap[$key] = $pkField;
        }

        if (!isset($name)) {
            $name = $toTable->getName().'_'.implode('_', array_keys($keymap));
        }
        $this->relations[$name] = [
            'name' => $name,
            'table' => $toTable,
            'keymap' => $keymap,
            'many' => false,
            'pivot' => null,
            'pivot_keymap' => [],
            'sql' => $sql,
            'par' => $par
        ];
        return $this;
    }
    /**
     * Create a relation where each record has many linked records in another table but using a liking table
     * @method belongsTo
     * @param  Table   $toTable       the related table definition
     * @param  Table   $pivot         the pivot table definition
     * @param  string|null       $name          the name of the relation (defaults to the related table name)
     * @param  string|array|null $toTableColumn the local columns pointing to the pivot table
     * @param  string|array|null $localColumn   the pivot columns pointing to the related table PK
     * @return self
     */
    public function manyToMany(
        Table $toTable,
        Table $pivot,
        $name = null,
        $toTableColumn = null,
        $localColumn = null
    ) : Table {
        $pivotColumns = $pivot->getColumns();

        $keymap = [];
        if (!isset($toTableColumn)) {
            $toTableColumn = [];
        }
        if (!is_array($toTableColumn)) {
            $toTableColumn = [$toTableColumn];
        }
        foreach ($this->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($toTableColumn[$pkField])) {
                $key = $toTableColumn[$pkField];
            } elseif (isset($toTableColumn[$k])) {
                $key = $toTableColumn[$k];
            } else {
                $key = $this->getName().'_'.$pkField;
            }
            if (!in_array($key, $pivotColumns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $keymap[$pkField] = $key;
        }

        $pivotKeymap = [];
        if (!isset($localColumn)) {
            $localColumn = [];
        }
        if (!is_array($localColumn)) {
            $localColumn = [$localColumn];
        }
        foreach ($toTable->getPrimaryKey() as $k => $pkField) {
            $key = null;
            if (isset($localColumn[$pkField])) {
                $key = $localColumn[$pkField];
            } elseif (isset($localColumn[$k])) {
                $key = $localColumn[$k];
            } else {
                $key = $toTable->getName().'_'.$pkField;
            }
            if (!in_array($key, $pivotColumns)) {
                throw new ORMException('Missing foreign key mapping');
            }
            $pivotKeymap[$key] = $pkField;
        }

        if (!isset($name)) {
            $name = $toTable->getName().'_'.implode('_', array_keys($keymap));
        }
        $this->relations[$name] = [
            'name' => $name,
            'table' => $toTable,
            'keymap' => $keymap,
            'many' => true,
            'pivot' => $pivot,
            'pivot_keymap' => $pivotKeymap,
            'sql' => null,
            'par' => []
        ];
        return $this;
    }
    /**
     * Create an advanced relation using the internal array format
     * @method addRelation
     * @param  string            $name          the name of the relation (defaults to the related table name)
     * @param  array             $relation      the relation definition
     * @return self
     */
    public function addRelation(string $name, array $relation)
    {
        $relation['name'] = $name;
        $this->relations[$name] = $relation;
        return $this;
    }
    /**
     * Does the definition have related tables
     * @method hasRelations
     * @return boolean
     */
    public function hasRelations() : bool
    {
        return count($this->relations) > 0;
    }
    /**
     * Get all relation definitions
     * @method getRelations
     * @return array       the relation definitions
     */
    public function getRelations() : array
    {
        return $this->relations;
    }
    /**
     * Check if a named relation exists
     * @method hasRelation
     * @param  string      $name the name to search for
     * @return boolean           does the relation exist
     */
    public function hasRelation(string $name) : bool
    {
        return isset($this->relations[$name]);
    }
    /**
     * Get a relation by name
     * @method getRelation
     * @param  string      $name the name to search for
     * @return array             the relation definition
     */
    public function getRelation(string $name) : array
    {
        return $this->relations[$name] ?? null;
    }
    /**
     * Rename a relation
     * @method renameRelation
     * @param  string      $name the name to search for
     * @param  string      $new  the new name for the relation
     * @return array             the relation definition
     */
    public function renameRelation(string $name, string $new) : array
    {
        if (!isset($this->relations[$name])) {
            throw new ORMException("Relation not found");
        }
        if (isset($this->relations[$new])) {
            throw new ORMException("A relation with that name already exists");
        }
        $temp = $this->relations[$name];
        $temp['name'] = $new;
        $this->relations[$new] = $temp;
        unset($this->relations[$name]);
        return $this->relations[$new] ?? null;
    }
}
