<?php

namespace vakata\orm;

use vakata\database\DatabaseInterface;

class TableDefinition
{
    protected $data = [];
    protected $relations = [];

    public static function fromTableName(DatabaseInterface $db, string $table)
    {
        $definition = new TableDefinition($table);
        $columns = [];
        $primary = [];
        switch ($db->driver()) {
            case 'mysql':
            case 'mysqli':
                foreach ($db->all('SHOW FULL COLUMNS FROM '.$table) as $data) {
                    $columns[$data['Field']] = $data;
                    if ($data['Key'] == 'PRI') {
                        $primary[] = $data['Field'];
                    }
                }
                break;
            case 'postgre':
            case 'oracle':
                $columns = $db->all(
                    'SELECT * FROM information_schema.columns WHERE table_name = ?',
                    [ $table ],
                    'column_name'
                );
                $tmp = $db->one(
                    'SELECT constraint_name FROM information_schema.table_constraints '.
                    'WHERE table_name = ? AND constraint_type = ?',
                    [ $table, 'PRIMARY KEY' ]
                );
                if ($tmp) {
                    $primary = $db->all(
                        'SELECT column_name FROM information_schema.key_column_usage WHERE table_name = ? AND constraint_name = ?',
                        [ $table, $tmp ]
                    );
                }
                break;
            default:
                throw new ORMException('Driver is not supported: '.$database->driver(), 500);
        }
        // TODO: foreign keys
        return $definition
            ->addColumns($columns)
            ->setPrimaryKey($primary);
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
    ) : TableDefinition
    {
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
    ) : TableDefinition
    {
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
    ) : TableDefinition
    {
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
    ) : TableDefinition
    {
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
