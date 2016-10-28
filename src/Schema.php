<?php
namespace vakata\orm;

use vakata\database\DatabaseInterface;

class Schema implements \JsonSerializable
{
    protected $db;
    protected $tables = [];

    /**
     * Create an instance
     * @param  DatabaseInterface $this->db      the database connection
     * @param  callable|null     $creator optional function used to create all necessary classes
     */
    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
        $this->tables = [];
    }

    /**
     * Add a table definition to the schema (most of the time you can rely on the autodetected definitions)
     * @param  Table $definition the definition
     * @return  self
     */
    public function addTable(Table $definition) : Schema
    {
        if (!isset($this->tables[$definition->getName()])) {
            $this->tables[$definition->getName()] = $definition;
        }
        return $this;
    }
    /**
     * Autodetect a definition by table name and add it to the schema.
     * @param  string            $table the table to analyze
     * @param  bool|boolean      $detectRelations should relations be extracted - defaults to `true`
     * @return  the newly added definition
     */
    public function addTableByName(string $table, bool $detectRelations = true) : Table
    {
        if (isset($this->tables[$table])) {
            return $this->tables[$table];
        }
        $definition = new Table($table);
        $columns = [];
        $primary = [];
        $comment = null;
        switch ($this->db->driver()) {
            case 'mysql':
            case 'mysqli':
                foreach ($this->db->all("SHOW FULL COLUMNS FROM {$table}") as $data) {
                    $columns[$data['Field']] = $data;
                    if ($data['Key'] == 'PRI') {
                        $primary[] = $data['Field'];
                    }
                }
                $comment = (string)$this->db->one(
                    "SELECT table_comment FROM information_schema.tables WHERE table_schema = ? AND table_name = ?",
                    [ $this->db->name(), $table ]
                );
                break;
            case 'postgre':
                $columns = $this->db->all(
                    "SELECT * FROM information_schema.columns WHERE table_schema = ? AND table_name = ?",
                    [ $this->db->name(), $table ],
                    'column_name'
                );
                $pkname = $this->db->one(
                    "SELECT constraint_name FROM information_schema.table_constraints
                    WHERE table_schema = ? AND table_name = ? AND constraint_type = ?",
                    [ $this->db->name(), $table, 'PRIMARY KEY' ]
                );
                if ($pkname) {
                    $primary = $this->db->all(
                        "SELECT column_name FROM information_schema.key_column_usage
                        WHERE table_schema = ? AND table_name = ? AND constraint_name = ?",
                        [ $this->db->name(), $table, $pkname ]
                    );
                }
                break;
            case 'oracle':
                $columns = $this->db->all(
                    "SELECT * FROM all_tab_cols WHERE table_name = ? AND owner = ?",
                    [ strtoupper($table), $this->db->name() ],
                    'COLUMN_NAME'
                );
                $owner = $this->db->name(); // current($columns)['OWNER'];
                $pkname = $this->db->one(
                    "SELECT constraint_name FROM all_constraints
                    WHERE table_name = ? AND constraint_type = ? AND owner = ?",
                    [ strtoupper($table), 'P', $owner ]
                );
                if ($pkname) {
                    $primary = $this->db->all(
                        "SELECT column_name FROM all_cons_columns
                        WHERE table_name = ? AND constraint_name = ? AND owner = ?",
                        [ strtoupper($table), $pkname, $owner ]
                    );
                }
                break;
            default:
                throw new ORMException('Driver is not supported: '.$this->db->driver(), 500);
        }
        if (!count($columns)) {
            throw new ORMException('Table not found by name');
        }
        $definition
            ->addColumns($columns)
            ->setPrimaryKey($primary)
            ->setComment($comment);
        $this->addTable($definition);

        if ($detectRelations) {
            switch ($this->db->driver()) {
                case 'mysql':
                case 'mysqli':
                    // relations where the current table is referenced
                    // assuming current table is on the "one" end having "many" records in the referencing table
                    // resulting in a "hasMany" or "manyToMany" relationship (if a pivot table is detected)
                    $relations = [];
                    foreach ($this->db->all(
                        "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_COLUMN_NAME
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME = ?",
                        [ $this->db->name(), $this->db->name(), $table ]
                    ) as $relation) {
                        $relations[$relation['CONSTRAINT_NAME']]['table'] = $relation['TABLE_NAME'];
                        $relations[$relation['CONSTRAINT_NAME']]['keymap'][$relation['REFERENCED_COLUMN_NAME']] = $relation['COLUMN_NAME'];
                    }
                    foreach ($relations as $data) {
                        $rtable = $this->getTable($data['table']); // ?? $this->addTableByName($data['table'], false);
                        $columns = [];
                        foreach ($rtable->getColumns() as $column) {
                            if (!in_array($column, $data['keymap'])) {
                                $columns[] = $column;
                            }
                        }
                        $foreign = [];
                        $usedcol = [];
                        if (count($columns)) {
                            foreach ($this->db->all(
                                "SELECT
                                    TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, 
                                    REFERENCED_COLUMN_NAME, REFERENCED_TABLE_NAME
                                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                WHERE
                                    TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME IN (??) AND
                                    REFERENCED_TABLE_NAME IS NOT NULL",
                                [ $this->db->name(), $data['table'], $columns ]
                            ) as $relation) {
                                $foreign[$relation['CONSTRAINT_NAME']]['table'] = $relation['REFERENCED_TABLE_NAME'];
                                $foreign[$relation['CONSTRAINT_NAME']]['keymap'][$relation['COLUMN_NAME']] = $relation['REFERENCED_COLUMN_NAME'];
                                $usedcol[] = $relation['COLUMN_NAME'];
                            }
                        }
                        if (count($foreign) === 1 && !count(array_diff($columns, $usedcol))) {
                            $foreign = current($foreign);
                            $relname = $foreign['table'];
                            $cntr = 1;
                            while ($definition->hasRelation($relname)) {
                                $relname = $foreign['table'] . '_' . (++ $cntr);
                            }
                            $definition->addRelation(
                                $relname,
                                [
                                    'name' => $relname,
                                    'table' => $this->getTable($foreign['table']),
                                    'keymap' => $data['keymap'],
                                    'many' => true,
                                    'pivot' => $rtable,
                                    'pivot_keymap' => $foreign['keymap'],
                                    'sql' => null,
                                    'par' => []
                                ]
                            );
                        } else {
                            $relname = $data['table'];
                            $cntr = 1;
                            while ($definition->hasRelation($relname)) {
                                $relname = $data['table'] . '_' . (++ $cntr);
                            }
                            $definition->addRelation(
                                $relname,
                                [
                                    'name' => $relname,
                                    'table' => $this->getTable($data['table']),
                                    'keymap' => $data['keymap'],
                                    'many' => true,
                                    'pivot' => null,
                                    'pivot_keymap' => [],
                                    'sql' => null,
                                    'par' => []
                                ]
                            );
                        }
                    }
                    // relations where the current table references another table
                    // assuming current table is linked to "one" record in the referenced table
                    // resulting in a "belongsTo" relationship
                    $relations = [];
                    foreach ($this->db->all(
                        "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL",
                        [ $this->db->name(), $table ]
                    ) as $relation) {
                        $relations[$relation['CONSTRAINT_NAME']]['table'] = $relation['REFERENCED_TABLE_NAME'];
                        $relations[$relation['CONSTRAINT_NAME']]['keymap'][$relation['COLUMN_NAME']] = $relation['REFERENCED_COLUMN_NAME'];
                    }
                    foreach ($relations as $name => $data) {
                        $relname = $data['table'];
                        $cntr = 1;
                        while ($definition->hasRelation($relname)) {
                            $relname = $data['table'] . '_' . (++ $cntr);
                        }
                        $definition->addRelation(
                            $relname,
                            [
                                'name' => $relname,
                                'table' => $this->getTable($data['table']),
                                'keymap' => $data['keymap'],
                                'many' => false,
                                'pivot' => null,
                                'pivot_keymap' => [],
                                'sql' => null,
                                'par' => []
                            ]
                        );
                    }
                    break;
                case 'oracle':
                    // relations where the current table is referenced
                    // assuming current table is on the "one" end having "many" records in the referencing table
                    // resulting in a "hasMany" or "manyToMany" relationship (if a pivot table is detected)
                    $relations = [];
                    foreach ($this->db->all(
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
                        $rtable = $this->getTable($data['table']); // ?? $this->addTableByName($data['table'], false);
                        $columns = [];
                        foreach ($rtable->getColumns() as $column) {
                            if (!in_array($column, $data['keymap'])) {
                                $columns[] = $column;
                            }
                        }
                        $foreign = [];
                        $usedcol = [];
                        if (count($columns)) {
                            foreach ($this->db->all(
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
                            $rcolumns = $this->db->all(
                                "SELECT COLUMN_NAME FROM all_cons_columns WHERE OWNER = ? AND CONSTRAINT_NAME = ? ORDER BY POSITION",
                                [ $owner, current($foreign['keymap']) ]
                            );
                            foreach ($foreign['keymap'] as $column => $related) {
                                $foreign['keymap'][$column] = array_shift($rcolumns);
                            }
                            $relname = $foreign['table'];
                            $cntr = 1;
                            while ($definition->hasRelation($relname)) {
                                $relname = $foreign['table'] . '_' . (++ $cntr);
                            }
                            $definition->addRelation(
                                $relname,
                                [
                                    'name' => $relname,
                                    'table' => $this->getTable($foreign['table']),
                                    'keymap' => $data['keymap'],
                                    'many' => true,
                                    'pivot' => $rtable,
                                    'pivot_keymap' => $foreign['keymap'],
                                    'sql' => null,
                                    'par' => []
                                ]
                            );
                        } else {
                            $relname = $data['table'];
                            $cntr = 1;
                            while ($definition->hasRelation($relname)) {
                                $relname = $data['table'] . '_' . (++ $cntr);
                            }
                            $definition->addRelation(
                                $relname,
                                [
                                    'name' => $relname,
                                    'table' => $this->getTable($data['table']),
                                    'keymap' => $data['keymap'],
                                    'many' => true,
                                    'pivot' => null,
                                    'pivot_keymap' => [],
                                    'sql' => null,
                                    'par' => []
                                ]
                            );
                        }
                    }
                    // relations where the current table references another table
                    // assuming current table is linked to "one" record in the referenced table
                    // resulting in a "belongsTo" relationship
                    $relations = [];
                    foreach ($this->db->all(
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
                        $rcolumns = $this->db->all(
                            "SELECT COLUMN_NAME FROM all_cons_columns WHERE OWNER = ? AND CONSTRAINT_NAME = ? ORDER BY POSITION",
                            [ $owner, current($data['keymap']) ]
                        );
                        foreach ($data['keymap'] as $column => $related) {
                            $data['keymap'][$column] = array_shift($rcolumns);
                        }
                        $relname = $data['table'];
                        $cntr = 1;
                        while ($definition->hasRelation($relname)) {
                            $relname = $data['table'] . '_' . (++ $cntr);
                        }
                        $definition->addRelation(
                            $relname,
                            [
                                'name' => $relname,
                                'table' => $this->getTable($data['table']),
                                'keymap' => $data['keymap'],
                                'many' => false,
                                'pivot' => null,
                                'pivot_keymap' => [],
                                'sql' => null,
                                'par' => []
                            ]
                        );
                    }
                    break;
                default:
                    throw new ORMException('Relations discovery is not supported: '.$this->db->driver(), 500);
            }
        }
        return $definition;
    }
    /**
     * Does the schema have a given table.
     * @param  string        $search the table name
     * @return bool                does the schema contain this table
     */
    public function hasTable(string $search) : bool
    {
        return isset($this->tables[$search]);
    }
    /**
     * Get an existing definition.
     * @param  string        $search     the table name
     * @param  string        $autodetect load the definition from the database if not present - defaults to `true`
     * @return Table                     the table definition
     */
    public function getTable(string $search, bool $autodetect = true)
    {
        $table = $this->tables[$search] ?? ($autodetect ? $this->addTableByName($search) : null);
        if (!$table) {
            throw new ORMException('Table not found');
        }
        return $table;
    }
    /**
     * Add all tables from database.
     * @return self
     */
    public function addAllTables()
    {
        $tables = [];
        switch ($this->db->driver()) {
            case 'mysql':
            case 'mysqli':
            case 'postgre':
                $tables = $this->db->all(
                    "SELECT table_name FROM information_schema.tables where table_schema = ?",
                    $this->db->name()
                );
                break;
            case 'oracle':
                $tables = $this->db->all(
                    "SELECT TABLE_NAME FROM ALL_TABLES where OWNER = ?",
                    $this->db->name()
                );
                break;
            default:
                throw new ORMException('Unsupported driver');
        }
        foreach ($tables as $table) {
            $this->addTableByName($table);
        }
        return $this;
    }
    /**
     * Get the full schema as an array that you can serialize and store
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($table) {
            return [
                'name' => $table->getName(),
                'pkey' => $table->getPrimaryKey(),
                'comment' => $table->getComment(),
                'columns' => array_map(function ($column) {
                    return [
                        'name' => $column->getName(),
                        'type' => $column->getType(),
                        'comment' => $column->getComment(),
                        'values' => $column->getValues(),
                        'default' => $column->getDefault(),
                        'nullable' => $column->isNullable()
                    ];
                }, $table->getFullColumns()),
                'relations' => array_map(function ($relation) {
                    $relation['table'] = $relation['table']->getName();
                    if ($relation['pivot']) {
                        $relation['pivot'] = $relation['pivot']->getName();
                    }
                    return $relation;
                }, $table->getRelations())
            ];
        }, $this->tables);
    }
    public function jsonSerialize()
    {
        return $this->toArray();
    }
    /**
     * Load the schema data from a schema definition array (obtained from toArray)
     * @param  array        $data the schema definition
     * @return self
     */
    public function fromArray(array $data)
    {
        foreach ($data as $tableData) {
            $this->addTable(
                (new Table($tableData['name']))
                        ->setPrimaryKey($tableData['pkey'])
                        ->setComment($tableData['comment'])
                        ->addColumns($tableData['columns'])
            );
        }
        foreach ($data as $tableData) {
            $table = $this->getTable($tableData['name']);
            foreach ($tableData['relations'] as $relationName => $relationData) {
                $relationData['table'] = $this->getTable($relationData['table']);
                if ($relationData['pivot']) {
                    $relationData['pivot'] = $this->getTable($relationData['pivot']);
                }
                $table->addRelation($relationName, $relationData);
            }
        }
        return $this;
    }
    /**
     * Get a query object for a table
     * @param  Table|string        $table the table definition or name
     * @return \vakata\orm\Query
     */
    public function query($table)
    {
        if (is_string($table)) {
            $table = $this->getTable($table);
        }
        return new Query($this->db, $table);
    }
    public function __call($method, $args)
    {
        return $this->query($method);
    }
}