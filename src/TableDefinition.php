<?php
namespace vakata\orm;

use vakata\database\DatabaseInterface;

/**
 * A table definition
 */
class TableDefinition
{
    protected static $definitions = [];

    protected $data = [];
    protected $relations = [];

    /**
     * Create an instance from a table name
     * @method fromDatabase
     * @param  DatabaseInterface $db              the database instance
     * @param  string            $table           the table to parse
     * @param  bool|boolean      $detectRelations should relations be extracted - defaults to `true`
     * @return TableDefinition                    the table definition
     */
    public static function fromDatabase(DatabaseInterface $db, string $table, bool $detectRelations = true)
    {
        if (isset(static::$definitions[(string)$db->name() . '.' . $table])) {
            return static::$definitions[(string)$db->name() . '.' . $table];
        }

        $definition = new TableDefinition($table);
        $columns = [];
        $primary = [];
        switch ($db->driver()) {
            case 'mysql':
            case 'mysqli':
                foreach ($db->all("SHOW FULL COLUMNS FROM {$table}") as $data) {
                    $columns[$data['Field']] = $data;
                    if ($data['Key'] == 'PRI') {
                        $primary[] = $data['Field'];
                    }
                }
                break;
            case 'postgre':
                $columns = $db->all(
                    "SELECT * FROM information_schema.columns WHERE table_name = ?",
                    [ $table ],
                    'column_name'
                );
                $pkname = $db->one(
                    "SELECT constraint_name FROM information_schema.table_constraints
                     WHERE table_name = ? AND constraint_type = ?",
                    [ $table, 'PRIMARY KEY' ]
                );
                if ($pkname) {
                    $primary = $db->all(
                        "SELECT column_name FROM information_schema.key_column_usage
                         WHERE table_name = ? AND constraint_name = ?",
                        [ $table, $pkname ]
                    );
                }
                break;
            case 'oracle':
                $columns = $db->all(
                    "SELECT * FROM all_tab_cols WHERE table_name = ?",
                    [ strtoupper($table) ],
                    'COLUMN_NAME'
                );
                $owner = current($columns)['OWNER'];
                $pkname = $db->one(
                    "SELECT constraint_name FROM all_constraints
                     WHERE table_name = ? AND constraint_type = ? AND owner = ?",
                    [ strtoupper($table), 'P', $owner ]
                );
                if ($pkname) {
                    $primary = $db->all(
                        "SELECT column_name FROM all_cons_columns
                         WHERE table_name = ? AND constraint_name = ? AND owner = ?",
                        [ strtoupper($table), $pkname, $owner ]
                    );
                }
                break;
            default:
                throw new ORMException('Driver is not supported: '.$database->driver(), 500);
        }
        static::$definitions[(string)$db->name() . '.' . $table] = $definition->addColumns($columns)->setPrimaryKey($primary);

        if ($detectRelations) {
            switch ($db->driver()) {
                case 'mysql':
                case 'mysqli':
                    // relations where the current table is referenced
                    // assuming current table is on the "one" end having "many" records in the referencing table
                    // resulting in a "hasMany" or "manyToMany" relationship (if a pivot table is detected)
                    $relations = [];
                    foreach ($db->all(
                        "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_COLUMN_NAME
                         FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                         WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME = ?",
                        [ $db->name(), $db->name(), $table ]
                    ) as $relation) {
                        $relations[$relation['CONSTRAINT_NAME']]['table'] = $relation['TABLE_NAME'];
                        $relations[$relation['CONSTRAINT_NAME']]['keymap'][$relation['REFERENCED_COLUMN_NAME']] = $relation['COLUMN_NAME'];
                    }
                    foreach ($relations as $data) {
                        $rtable = static::fromDatabase($db, $data['table'], false);
                        $columns = [];
                        foreach ($rtable->getColumns() as $column) {
                            if (!in_array($column, $data['keymap'])) {
                                $columns[] = $column;
                            }
                        }
                        $foreign = [];
                        $usedcol = [];
                        if (count($columns)) {
                            foreach ($db->all(
                                "SELECT
                                    TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
                                    REFERENCED_COLUMN_NAME, REFERENCED_TABLE_NAME
                                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                 WHERE
                                    TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME IN (??) AND
                                    REFERENCED_TABLE_NAME IS NOT NULL",
                                [ $db->name(), $data['table'], $columns ]
                            ) as $relation) {
                                $foreign[$relation['CONSTRAINT_NAME']]['table'] = $relation['REFERENCED_TABLE_NAME'];
                                $foreign[$relation['CONSTRAINT_NAME']]['keymap'][$relation['COLUMN_NAME']] = $relation['REFERENCED_COLUMN_NAME'];
                                $usedcol[] = $relation['COLUMN_NAME'];
                            }
                        }
                        if (count($foreign) === 1 && !count(array_diff($columns, $usedcol))) {
                            $foreign = current($foreign);
                            $definition->relations[$foreign['table']] = [
                                'name' => $foreign['table'],
                                'table' => static::fromDatabase($db, $foreign['table'], true),
                                'keymap' => $data['keymap'],
                                'many' => true,
                                'pivot' => $rtable,
                                'pivot_keymap' => $foreign['keymap'],
                                'sql' => null,
                                'par' => []
                            ];
                        } else {
                            $definition->relations[$data['table']] = [
                                'name' => $data['table'],
                                'table' => static::fromDatabase($db, $data['table'], true),
                                'keymap' => $data['keymap'],
                                'many' => true,
                                'pivot' => null,
                                'pivot_keymap' => [],
                                'sql' => null,
                                'par' => []
                            ];
                        }
                    }
                    // relations where the current table references another table
                    // assuming current table is linked to "one" record in the referenced table
                    // resulting in a "belongsTo" relationship
                    $relations = [];
                    foreach ($db->all(
                        "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                         FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL",
                        [ $db->name(), $table ]
                    ) as $relation) {
                        $relations[$relation['CONSTRAINT_NAME']]['table'] = $relation['REFERENCED_TABLE_NAME'];
                        $relations[$relation['CONSTRAINT_NAME']]['keymap'][$relation['COLUMN_NAME']] = $relation['REFERENCED_COLUMN_NAME'];
                    }
                    foreach ($relations as $name => $data) {
                        $definition->relations[$data['table']] = [
                            'name' => $data['table'],
                            'table' => static::fromDatabase($db, $data['table'], true),
                            'keymap' => $data['keymap'],
                            'many' => false,
                            'pivot' => null,
                            'pivot_keymap' => [],
                            'sql' => null,
                            'par' => []
                        ];
                    }
                    break;
                case 'oracle':
                    // relations where the current table is referenced
                    // assuming current table is on the "one" end having "many" records in the referencing table
                    // resulting in a "hasMany" or "manyToMany" relationship (if a pivot table is detected)
                    $relations = [];
                    foreach ($db->all(
                        "SELECT ac.TABLE_NAME, ac.CONSTRAINT_NAME, cc.COLUMN_NAME
                         FROM all_constraints ac
                         LEFT JOIN all_cons_columns cc ON cc.OWNER = ac.OWNER AND cc.CONSTRAINT_NAME = ac.CONSTRAINT_NAME
                         WHERE ac.OWNER = ? AND ac.R_OWNER = ? AND ac.R_CONSTRAINT_NAME = ? AND ac.CONSTRAINT_TYPE = ?
                         ORDER BY cc.POSITION",
                        [ $owner, $owner, $pkname, 'R' ]
                    ) as $k => $relation) {
                        $relations[$relation['CONSTRAINT_NAME']]['table'] = $relation['TABLE_NAME'];
                        $relations[$relation['CONSTRAINT_NAME']]['keymap'][$primary[$k]] = $relation['COLUMN_NAME'];
                    }
                    foreach ($relations as $data) {
                        $rtable = static::fromDatabase($db, $data['table'], false);
                        $columns = [];
                        foreach ($rtable->getColumns() as $column) {
                            if (!in_array($column, $data['keymap'])) {
                                $columns[] = $column;
                            }
                        }
                        $foreign = [];
                        $usedcol = [];
                        if (count($columns)) {
                            foreach ($db->all(
                                "SELECT
                                    cc.COLUMN_NAME, ac.CONSTRAINT_NAME, rc.TABLE_NAME AS REFERENCED_TABLE_NAME, ac.R_CONSTRAINT_NAME
                                 FROM all_constraints ac
                                 JOIN all_constraints rc ON rc.CONSTRAINT_NAME = ac.R_CONSTRAINT_NAME AND rc.OWNER = ac.OWNER
                                 LEFT JOIN all_cons_columns cc ON cc.OWNER = ac.OWNER AND cc.CONSTRAINT_NAME = ac.CONSTRAINT_NAME
                                 WHERE
                                    ac.OWNER = ? AND ac.R_OWNER = ? AND ac.TABLE_NAME = ? AND ac.CONSTRAINT_TYPE = ? AND 
                                    cc.COLUMN_NAME IN (??)
                                 ORDER BY POSITION",
                                [ $owner, $owner, $data['table'], 'R', $columns ]
                            ) as $k => $relation) {
                                $foreign[$relation['CONSTRAINT_NAME']]['table'] = $relation['REFERENCED_TABLE_NAME'];
                                $foreign[$relation['CONSTRAINT_NAME']]['keymap'][$relation['COLUMN_NAME']] = $relation['R_CONSTRAINT_NAME'];
                                $usedcol[] = $relation['COLUMN_NAME'];
                            }
                        }
                        if (count($foreign) === 1 && !count(array_diff($columns, $usedcol))) {
                            $foreign = current($foreign);
                            $rcolumns = $db->all(
                                "SELECT COLUMN_NAME FROM all_cons_columns WHERE OWNER = ? AND CONSTRAINT_NAME = ? ORDER BY POSITION",
                                [ $owner, current($foreign['keymap']) ]
                            );
                            foreach ($foreign['keymap'] as $column => $related) {
                                $foreign['keymap'][$column] = array_shift($rcolumns);
                            }
                            $definition->relations[$foreign['table']] = [
                                'name' => $foreign['table'],
                                'table' => static::fromDatabase($db, $foreign['table'], true),
                                'keymap' => $data['keymap'],
                                'many' => true,
                                'pivot' => $rtable,
                                'pivot_keymap' => $foreign['keymap'],
                                'sql' => null,
                                'par' => []
                            ];
                        } else {
                            $definition->relations[$data['table']] = [
                                'name' => $data['table'],
                                'table' => static::fromDatabase($db, $data['table'], true),
                                'keymap' => $data['keymap'],
                                'many' => true,
                                'pivot' => null,
                                'pivot_keymap' => [],
                                'sql' => null,
                                'par' => []
                            ];
                        }
                    }
                    // relations where the current table references another table
                    // assuming current table is linked to "one" record in the referenced table
                    // resulting in a "belongsTo" relationship
                    $relations = [];
                    foreach ($db->all(
                        "SELECT ac.CONSTRAINT_NAME, cc.COLUMN_NAME, rc.TABLE_NAME AS REFERENCED_TABLE_NAME, ac.R_CONSTRAINT_NAME
                         FROM all_constraints ac
                         JOIN all_constraints rc ON rc.CONSTRAINT_NAME = ac.R_CONSTRAINT_NAME AND rc.OWNER = ac.OWNER
                         LEFT JOIN all_cons_columns cc ON cc.OWNER = ac.OWNER AND cc.CONSTRAINT_NAME = ac.CONSTRAINT_NAME
                         WHERE ac.OWNER = ? AND ac.R_OWNER = ? AND ac.TABLE_NAME = ? AND ac.CONSTRAINT_TYPE = ?
                         ORDER BY cc.POSITION",
                        [ $owner, $owner, strtoupper($table), 'R' ]
                    ) as $relation) {
                        $relations[$relation['CONSTRAINT_NAME']]['table'] = $relation['REFERENCED_TABLE_NAME'];
                        $relations[$relation['CONSTRAINT_NAME']]['keymap'][$relation['COLUMN_NAME']] = $relation['R_CONSTRAINT_NAME'];
                    }
                    foreach ($relations as $name => $data) {
                        $rcolumns = $db->all(
                            "SELECT COLUMN_NAME FROM all_cons_columns WHERE OWNER = ? AND CONSTRAINT_NAME = ? ORDER BY POSITION",
                            [ $owner, current($data['keymap']) ]
                        );
                        foreach ($data['keymap'] as $column => $related) {
                            $data['keymap'][$column] = array_shift($rcolumns);
                        }
                        $definition->relations[$data['table']] = [
                            'name' => $data['table'],
                            'table' => static::fromDatabase($db, $data['table'], true),
                            'keymap' => $data['keymap'],
                            'many' => false,
                            'pivot' => null,
                            'pivot_keymap' => [],
                            'sql' => null,
                            'par' => []
                        ];
                    }
                    break;
                default:
                    throw new ORMException('Relations discovery is not supported: '.$database->driver(), 500);
            }
        }
        return $definition;
    }

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
            'primary' => []
        ];
        $this->relations = [];
    }
    /**
     * Add a column to the definition
     * @method addColumn
     * @param  string    $column     the column name
     * @param  array     $definition optional array of data associated with the column
     * @return  self
     */
    public function addColumn(string $column, array $definition = []) : TableDefinition
    {
        $this->data['columns'][$column] = $definition;
        return $this;
    }
    /**
     * Add columns to the definition
     * @method addColumns
     * @param  array      $columns key - value pairs, where each key is a column name and each value - array of info
     * @return  self
     */
    public function addColumns(array $columns) : TableDefinition
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
    public function setPrimaryKey($column) : TableDefinition
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
     * @param  TableDefinition   $toTable       the related table definition
     * @param  string|null       $name          the name of the relation (defaults to the related table name)
     * @param  string|array|null $toTableColumn the remote columns pointing to the PK in the current table
     * @param  string|null       $sql           additional where clauses to use, default to null
     * @param  array             $par           parameters for the above statement, defaults to an empty array
     * @return self
     */
    public function hasOne(
        TableDefinition $toTable,
        string $name = null,
        $toTableColumn = null,
        string $sql = null,
        array $par = []
    ) : TableDefinition {
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
     * @param  TableDefinition   $toTable       the related table definition
     * @param  string|null       $name          the name of the relation (defaults to the related table name)
     * @param  string|array|null $toTableColumn the remote columns pointing to the PK in the current table
     * @param  string|null       $sql           additional where clauses to use, default to null
     * @param  array             $par           parameters for the above statement, defaults to an empty array
     * @return self
     */
    public function hasMany(
        TableDefinition $toTable,
        string $name = null,
        $toTableColumn = null,
        $sql = null,
        array $par = []
    ) : TableDefinition {
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
     * @param  TableDefinition   $toTable       the related table definition
     * @param  string|null       $name          the name of the relation (defaults to the related table name)
     * @param  string|array|null $localColumn   the local columns pointing to the PK of the related table
     * @param  string|null       $sql           additional where clauses to use, default to null
     * @param  array             $par           parameters for the above statement, defaults to an empty array
     * @return self
     */
    public function belongsTo(
        TableDefinition $toTable,
        string $name = null,
        $localColumn = null,
        $sql = null,
        array $par = []
    ) : TableDefinition {
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
     * @param  TableDefinition   $toTable       the related table definition
     * @param  TableDefinition   $pivot         the pivot table definition
     * @param  string|null       $name          the name of the relation (defaults to the related table name)
     * @param  string|array|null $toTableColumn the local columns pointing to the pivot table
     * @param  string|array|null $localColumn   the pivot columns pointing to the related table PK
     * @return self
     */
    public function manyToMany(
        TableDefinition $toTable,
        TableDefinition $pivot,
        $name = null,
        $toTableColumn = null,
        $localColumn = null
    ) : TableDefinition {
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
}
