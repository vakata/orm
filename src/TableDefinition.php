<?php

namespace vakata\orm;

use vakata\database\DatabaseInterface;

class TableDefinition implements TableDefinitionInterface, \JsonSerializable
{
    protected $definition = [];

    public function __construct(DatabaseInterface $database, $table)
    {
        $this->definition = [
            'name' => $table,
            'primary_key' => [],
            'columns' => [],
            'definitions' => [],
            'indexed' => []
        ];
        switch ($database->driver()) {
            case 'mysql':
            case 'mysqli':
                foreach ($database->all('SHOW FULL COLUMNS FROM '.$table) as $data) {
                    $this->definition['columns'][] = $data['Field'];
                    $this->definition['definitions'][$data['Field']] = $data;
                    if ($data['Key'] == 'PRI') {
                        $this->definition['primary_key'][] = $data['Field'];
                    }
                }
                foreach ($database->all('SHOW INDEX FROM '.$table.' WHERE Index_type = \'FULLTEXT\'') as $data) {
                    $this->definition['indexed'][] = $data['Column_name'];
                }
                $this->definition['indexed'] = array_unique($this->definition['indexed']);
                break;
            case 'postgre':
            case 'oracle':
                $this->definition['definitions'] = $database->all(
                    'SELECT * FROM information_schema.columns WHERE table_name = ? ',
                    [$table],
                    'column_name'
                );
                $this->definition['columns'] = array_keys($this->definition['definitions']);
                $tmp = $database->one(
                    'SELECT constraint_name FROM information_schema.table_constraints '.
                    'WHERE table_name = ? AND constraint_type = ?',
                    [$table, 'PRIMARY KEY']
                );
                if ($tmp) {
                    $this->definition['primary_key'] = $database->all(
                        'SELECT column_name FROM information_schema.key_column_usage '.
                        'WHERE table_name = ? AND constraint_name = ?',
                        [$table, $tmp]
                    );
                }
                break;
            default:
                throw new ORMException('Driver is not supported: '.$database->driver(), 500);
        }
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

    public function getName()
    {
        return $this->definition['name'];
    }
    public function getPrimaryKey()
    {
        return $this->definition['primary_key'];
    }
    public function getColumns()
    {
        return $this->definition['columns'];
    }

    public function toArray()
    {
        return $this->definition;
    }
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
