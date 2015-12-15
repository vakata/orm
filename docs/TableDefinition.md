# vakata\orm\TableDefinition
TableDefinition is used when working with the \vakata\orm\Table class.

The class provides information about a table in the database, and autocollects that information when created.
## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\tabledefinition__construct)|Create an instance.|
|[getName](#vakata\orm\tabledefinitiongetname)|Get the table name.|
|[getPrimaryKey](#vakata\orm\tabledefinitiongetprimarykey)|Get the columns forming the primary key.|
|[getColumns](#vakata\orm\tabledefinitiongetcolumns)|Get a list of columns.|
|[toArray](#vakata\orm\tabledefinitiontoarray)|Get the current definition as an array.|

---



### vakata\orm\TableDefinition::__construct
Create an instance.  


```php
public function __construct (  
    \vakata\database\DatabaseInterface $database,  
    string $table  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$database` | `\vakata\database\DatabaseInterface` | a database instance |
| `$table` | `string` | the name of the table to be processed |

---


### vakata\orm\TableDefinition::getName
Get the table name.  


```php
public function getName () : string    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `string` | the name of the table |

---


### vakata\orm\TableDefinition::getPrimaryKey
Get the columns forming the primary key.  


```php
public function getPrimaryKey () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | array of primary key columns |

---


### vakata\orm\TableDefinition::getColumns
Get a list of columns.  


```php
public function getColumns () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | array of strings of column names |

---


### vakata\orm\TableDefinition::toArray
Get the current definition as an array.  


```php
public function toArray () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | the definition |

---

