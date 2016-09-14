# vakata\orm\TableDefinition
A table definition

## Methods

| Name | Description |
|------|-------------|
|[fromDatabase](#vakata\orm\tabledefinitionfromdatabase)|Create an instance from a table name|
|[__construct](#vakata\orm\tabledefinition__construct)|Create a new instance|
|[addColumn](#vakata\orm\tabledefinitionaddcolumn)|Add a column to the definition|
|[addColumns](#vakata\orm\tabledefinitionaddcolumns)|Add columns to the definition|
|[setPrimaryKey](#vakata\orm\tabledefinitionsetprimarykey)|Set the primary key|
|[getName](#vakata\orm\tabledefinitiongetname)|Get the table name|
|[getColumn](#vakata\orm\tabledefinitiongetcolumn)|Get a column definition|
|[getColumns](#vakata\orm\tabledefinitiongetcolumns)|Get all column names|
|[getFullColumns](#vakata\orm\tabledefinitiongetfullcolumns)|Get all column definitions|
|[getPrimaryKey](#vakata\orm\tabledefinitiongetprimarykey)|Get the primary key columns|
|[hasOne](#vakata\orm\tabledefinitionhasone)|Create a relation where each record has zero or one related rows in another table|
|[hasMany](#vakata\orm\tabledefinitionhasmany)|Create a relation where each record has zero, one or more related rows in another table|
|[belongsTo](#vakata\orm\tabledefinitionbelongsto)|Create a relation where each record belongs to another row in another table|
|[manyToMany](#vakata\orm\tabledefinitionmanytomany)|Create a relation where each record has many linked records in another table but using a liking table|
|[hasRelations](#vakata\orm\tabledefinitionhasrelations)|Does the definition have related tables|
|[getRelations](#vakata\orm\tabledefinitiongetrelations)|Get all relation definitions|
|[hasRelation](#vakata\orm\tabledefinitionhasrelation)|Check if a named relation exists|
|[getRelation](#vakata\orm\tabledefinitiongetrelation)|Get a relation by name|

---



### vakata\orm\TableDefinition::fromDatabase
Create an instance from a table name  


```php
public static function fromDatabase (  
    \DatabaseInterface $db,  
    string $table,  
    bool|boolean $detectRelations  
) : \TableDefinition    
```

|  | Type | Description |
|-----|-----|-----|
| `$db` | `\DatabaseInterface` | the database instance |
| `$table` | `string` | the table to parse |
| `$detectRelations` | `bool`, `boolean` | should relations be extracted - defaults to `true` |
|  |  |  |
| `return` | `\TableDefinition` | the table definition |

---


### vakata\orm\TableDefinition::__construct
Create a new instance  


```php
public function __construct (  
    string $name  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$name` | `string` | the table name |

---


### vakata\orm\TableDefinition::addColumn
Add a column to the definition  


```php
public function addColumn (  
    string $column,  
    array $definition  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$column` | `string` | the column name |
| `$definition` | `array` | optional array of data associated with the column |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\TableDefinition::addColumns
Add columns to the definition  


```php
public function addColumns (  
    array $columns  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$columns` | `array` | key - value pairs, where each key is a column name and each value - array of info |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\TableDefinition::setPrimaryKey
Set the primary key  


```php
public function setPrimaryKey (  
    array|string $column  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$column` | `array`, `string` | either a single column name or an array of column names |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\TableDefinition::getName
Get the table name  


```php
public function getName () : string    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `string` | the table name |

---


### vakata\orm\TableDefinition::getColumn
Get a column definition  


```php
public function getColumn (  
    string $column  
) : array, null    
```

|  | Type | Description |
|-----|-----|-----|
| `$column` | `string` | the column name to search for |
|  |  |  |
| `return` | `array`, `null` | the column details or `null` if the column does not exist |

---


### vakata\orm\TableDefinition::getColumns
Get all column names  


```php
public function getColumns () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | array of strings, where each element is a column name |

---


### vakata\orm\TableDefinition::getFullColumns
Get all column definitions  


```php
public function getFullColumns () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | key - value pairs, where each key is a column name and each value - the column data |

---


### vakata\orm\TableDefinition::getPrimaryKey
Get the primary key columns  


```php
public function getPrimaryKey () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | array of column names |

---


### vakata\orm\TableDefinition::hasOne
Create a relation where each record has zero or one related rows in another table  


```php
public function hasOne (  
    \TableDefinition $toTable,  
    string|null $name,  
    string|array|null $toTableColumn,  
    string|null $sql,  
    array $par  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$toTable` | `\TableDefinition` | the related table definition |
| `$name` | `string`, `null` | the name of the relation (defaults to the related table name) |
| `$toTableColumn` | `string`, `array`, `null` | the remote columns pointing to the PK in the current table |
| `$sql` | `string`, `null` | additional where clauses to use, default to null |
| `$par` | `array` | parameters for the above statement, defaults to an empty array |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\TableDefinition::hasMany
Create a relation where each record has zero, one or more related rows in another table  


```php
public function hasMany (  
    \TableDefinition $toTable,  
    string|null $name,  
    string|array|null $toTableColumn,  
    string|null $sql,  
    array $par  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$toTable` | `\TableDefinition` | the related table definition |
| `$name` | `string`, `null` | the name of the relation (defaults to the related table name) |
| `$toTableColumn` | `string`, `array`, `null` | the remote columns pointing to the PK in the current table |
| `$sql` | `string`, `null` | additional where clauses to use, default to null |
| `$par` | `array` | parameters for the above statement, defaults to an empty array |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\TableDefinition::belongsTo
Create a relation where each record belongs to another row in another table  


```php
public function belongsTo (  
    \TableDefinition $toTable,  
    string|null $name,  
    string|array|null $localColumn,  
    string|null $sql,  
    array $par  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$toTable` | `\TableDefinition` | the related table definition |
| `$name` | `string`, `null` | the name of the relation (defaults to the related table name) |
| `$localColumn` | `string`, `array`, `null` | the local columns pointing to the PK of the related table |
| `$sql` | `string`, `null` | additional where clauses to use, default to null |
| `$par` | `array` | parameters for the above statement, defaults to an empty array |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\TableDefinition::manyToMany
Create a relation where each record has many linked records in another table but using a liking table  


```php
public function manyToMany (  
    \TableDefinition $toTable,  
    \TableDefinition $pivot,  
    string|null $name,  
    string|array|null $toTableColumn,  
    string|array|null $localColumn  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$toTable` | `\TableDefinition` | the related table definition |
| `$pivot` | `\TableDefinition` | the pivot table definition |
| `$name` | `string`, `null` | the name of the relation (defaults to the related table name) |
| `$toTableColumn` | `string`, `array`, `null` | the local columns pointing to the pivot table |
| `$localColumn` | `string`, `array`, `null` | the pivot columns pointing to the related table PK |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\TableDefinition::hasRelations
Does the definition have related tables  


```php
public function hasRelations () : boolean    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `boolean` |  |

---


### vakata\orm\TableDefinition::getRelations
Get all relation definitions  


```php
public function getRelations () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | the relation definitions |

---


### vakata\orm\TableDefinition::hasRelation
Check if a named relation exists  


```php
public function hasRelation (  
    string $name  
) : boolean    
```

|  | Type | Description |
|-----|-----|-----|
| `$name` | `string` | the name to search for |
|  |  |  |
| `return` | `boolean` | does the relation exist |

---


### vakata\orm\TableDefinition::getRelation
Get a relation by name  


```php
public function getRelation (  
    string $name  
) : array    
```

|  | Type | Description |
|-----|-----|-----|
| `$name` | `string` | the name to search for |
|  |  |  |
| `return` | `array` | the relation definition |

---

