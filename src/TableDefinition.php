<?php
namespace vakata\orm;

use vakata\database\DatabaseInterface;

class TableDefinition
{
    protected static $definitions = [];

    protected $data = [];
    protected $relations = [];

    public static function fromDatabase(DatabaseInterface $db, string $table, bool $detectRelations = true)
    {
        if (isset(static::$definitions[$db->name() . '.' . $table])) {
            return static::$definitions[$db->name() . '.' . $table];
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
            case 'oracle':
                $columns = $db->all(
                    "SELECT * FROM information_schema.columns WHERE table_name = ?",
                    [ $table ],
                    'column_name'
                );
                $tmp = $db->one(
                    "SELECT constraint_name FROM information_schema.table_constraints
                     WHERE table_name = ? AND constraint_type = ?",
                    [ $table, 'PRIMARY KEY' ]
                );
                if ($tmp) {
                    $primary = $db->all(
                        "SELECT column_name FROM information_schema.key_column_usage
                         WHERE table_name = ? AND constraint_name = ?",
                        [ $table, $tmp ]
                    );
                }
                break;
            default:
                throw new ORMException('Driver is not supported: '.$database->driver(), 500);
        }
        static::$definitions[$db->name() . '.' . $table] = $definition->addColumns($columns)->setPrimaryKey($primary);

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
                default:
                    throw new ORMException('Relations discovery is not supported: '.$database->driver(), 500);
            }
        }
        return $definition;
    }

    public function __construct(string $name)
    {
        $this->data = [
            'name'    => $name,
            'columns' => [],
            'primary' => []
        ];
        $this->relations = [];
    }
    public function addColumn(string $column, array $definition = []) : TableDefinition
    {
        $this->data['columns'][$column] = $definition;
        return $this;
    }
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
    public function setPrimaryKey($column) : TableDefinition
    {
        if (!is_array($column)) {
            $column = [ $column ];
        }
        $this->data['primary'] = $column;
        return $this;
    }
    public function getName()
    {
        return $this->data['name'];
    }
    public function getColumn($column)
    {
        return $this->data['columns'][$column] ?? null;
    }
    public function getColumns()
    {
        return array_keys($this->data['columns']);
    }
    public function getFullColumns()
    {
        return $this->data['columns'];
    }
    public function getPrimaryKey()
    {
        return $this->data['primary'];
    }
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
    public function hasRelations() : bool
    {
        return count($this->relations) > 0;
    }
    public function getRelations() : array
    {
        return $this->relations;
    }
    public function hasRelation(string $name) : bool
    {
        return isset($this->relations[$name]);
    }
    public function getRelation(string $name) : array
    {
        return $this->relations[$name] ?? null;
    }
}
