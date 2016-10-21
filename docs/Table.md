# vakata\orm\Table
A table definition

## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\table__construct)|Create a new instance|
|[getComment](#vakata\orm\tablegetcomment)|Get the table comment|
|[setComment](#vakata\orm\tablesetcomment)|Set the table comment|
|[addColumn](#vakata\orm\tableaddcolumn)|Add a column to the definition|
|[addColumns](#vakata\orm\tableaddcolumns)|Add columns to the definition|
|[setPrimaryKey](#vakata\orm\tablesetprimarykey)|Set the primary key|
|[getName](#vakata\orm\tablegetname)|Get the table name|
|[getColumn](#vakata\orm\tablegetcolumn)|Get a column definition|
|[getColumns](#vakata\orm\tablegetcolumns)|Get all column names|
|[getFullColumns](#vakata\orm\tablegetfullcolumns)|Get all column definitions|
|[getPrimaryKey](#vakata\orm\tablegetprimarykey)|Get the primary key columns|
|[hasOne](#vakata\orm\tablehasone)|Create a relation where each record has zero or one related rows in another table|
|[hasMany](#vakata\orm\tablehasmany)|Create a relation where each record has zero, one or more related rows in another table|
|[belongsTo](#vakata\orm\tablebelongsto)|Create a relation where each record belongs to another row in another table|
|[manyToMany](#vakata\orm\tablemanytomany)|Create a relation where each record has many linked records in another table but using a liking table|
|[addRelation](#vakata\orm\tableaddrelation)|Create an advanced relation using the internal array format|
|[hasRelations](#vakata\orm\tablehasrelations)|Does the definition have related tables|
|[getRelations](#vakata\orm\tablegetrelations)|Get all relation definitions|
|[hasRelation](#vakata\orm\tablehasrelation)|Check if a named relation exists|
|[getRelation](#vakata\orm\tablegetrelation)|Get a relation by name|
|[renameRelation](#vakata\orm\tablerenamerelation)|Rename a relation|

---



### vakata\orm\Table::__construct
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


### vakata\orm\Table::getComment
Get the table comment  


```php
public function getComment () : string    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `string` | the table comment |

---


### vakata\orm\Table::setComment
Set the table comment  


```php
public function setComment (  
    string $comment  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$comment` | `string` | the table comment |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::addColumn
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


### vakata\orm\Table::addColumns
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


### vakata\orm\Table::setPrimaryKey
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


### vakata\orm\Table::getName
Get the table name  


```php
public function getName () : string    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `string` | the table name |

---


### vakata\orm\Table::getColumn
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


### vakata\orm\Table::getColumns
Get all column names  


```php
public function getColumns () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | array of strings, where each element is a column name |

---


### vakata\orm\Table::getFullColumns
Get all column definitions  


```php
public function getFullColumns () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | key - value pairs, where each key is a column name and each value - the column data |

---


### vakata\orm\Table::getPrimaryKey
Get the primary key columns  


```php
public function getPrimaryKey () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | array of column names |

---


### vakata\orm\Table::hasOne
Create a relation where each record has zero or one related rows in another table  


```php
public function hasOne (  
    \Table $toTable,  
    string|null $name,  
    string|array|null $toTableColumn,  
    string|null $sql,  
    array $par  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$toTable` | `\Table` | the related table definition |
| `$name` | `string`, `null` | the name of the relation (defaults to the related table name) |
| `$toTableColumn` | `string`, `array`, `null` | the remote columns pointing to the PK in the current table |
| `$sql` | `string`, `null` | additional where clauses to use, default to null |
| `$par` | `array` | parameters for the above statement, defaults to an empty array |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::hasMany
Create a relation where each record has zero, one or more related rows in another table  


```php
public function hasMany (  
    \Table $toTable,  
    string|null $name,  
    string|array|null $toTableColumn,  
    string|null $sql,  
    array $par  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$toTable` | `\Table` | the related table definition |
| `$name` | `string`, `null` | the name of the relation (defaults to the related table name) |
| `$toTableColumn` | `string`, `array`, `null` | the remote columns pointing to the PK in the current table |
| `$sql` | `string`, `null` | additional where clauses to use, default to null |
| `$par` | `array` | parameters for the above statement, defaults to an empty array |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::belongsTo
Create a relation where each record belongs to another row in another table  


```php
public function belongsTo (  
    \Table $toTable,  
    string|null $name,  
    string|array|null $localColumn,  
    string|null $sql,  
    array $par  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$toTable` | `\Table` | the related table definition |
| `$name` | `string`, `null` | the name of the relation (defaults to the related table name) |
| `$localColumn` | `string`, `array`, `null` | the local columns pointing to the PK of the related table |
| `$sql` | `string`, `null` | additional where clauses to use, default to null |
| `$par` | `array` | parameters for the above statement, defaults to an empty array |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::manyToMany
Create a relation where each record has many linked records in another table but using a liking table  


```php
public function manyToMany (  
    \Table $toTable,  
    \Table $pivot,  
    string|null $name,  
    string|array|null $toTableColumn,  
    string|array|null $localColumn  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$toTable` | `\Table` | the related table definition |
| `$pivot` | `\Table` | the pivot table definition |
| `$name` | `string`, `null` | the name of the relation (defaults to the related table name) |
| `$toTableColumn` | `string`, `array`, `null` | the local columns pointing to the pivot table |
| `$localColumn` | `string`, `array`, `null` | the pivot columns pointing to the related table PK |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::addRelation
Create an advanced relation using the internal array format  


```php
public function addRelation (  
    string $name,  
    array $relation  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$name` | `string` | the name of the relation (defaults to the related table name) |
| `$relation` | `array` | the relation definition |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::hasRelations
Does the definition have related tables  


```php
public function hasRelations () : boolean    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `boolean` |  |

---


### vakata\orm\Table::getRelations
Get all relation definitions  


```php
public function getRelations () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | the relation definitions |

---


### vakata\orm\Table::hasRelation
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


### vakata\orm\Table::getRelation
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


### vakata\orm\Table::renameRelation
Rename a relation  


```php
public function renameRelation (  
    string $name,  
    string $new  
) : array    
```

|  | Type | Description |
|-----|-----|-----|
| `$name` | `string` | the name to search for |
| `$new` | `string` | the new name for the relation |
|  |  |  |
| `return` | `array` | the relation definition |

---

