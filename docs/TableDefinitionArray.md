# vakata\orm\TableDefinitionArray
TableDefinitionArray is used when working with the \vakata\orm\Table class.

The class provides information about a table in the database.
Data is not autocollected (as with \vakata\orm\TableDefinition) - the class relies on data that is passed in.
## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\tabledefinitionarray__construct)|Create an instance.|
|[getName](#vakata\orm\tabledefinitionarraygetname)|Get the table name.|
|[getPrimaryKey](#vakata\orm\tabledefinitionarraygetprimarykey)|Get the columns forming the primary key.|
|[getColumns](#vakata\orm\tabledefinitionarraygetcolumns)|Get a list of columns.|
|[toArray](#vakata\orm\tabledefinitionarraytoarray)|Get the current definition as an array.|

---



### vakata\orm\TableDefinitionArray::__construct
Create an instance.  


```php
public function __construct (  
    string $table,  
    array $definition  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$table` | `string` | the table name |
| `$definition` | `array` | the table definition (array with at least "primary_key" and "columns" keys) |

---


### vakata\orm\TableDefinitionArray::getName
Get the table name.  


```php
public function getName () : string    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `string` | the name of the table |

---


### vakata\orm\TableDefinitionArray::getPrimaryKey
Get the columns forming the primary key.  


```php
public function getPrimaryKey () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | array of primary key columns |

---


### vakata\orm\TableDefinitionArray::getColumns
Get a list of columns.  


```php
public function getColumns () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | array of strings of column names |

---


### vakata\orm\TableDefinitionArray::toArray
Get the current definition as an array.  


```php
public function toArray () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | the definition |

---

