# vakata\orm\Table
A table abstraction with support for filtering, ordering, create, update, delete and array-like access and iteration.

## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\table__construct)|Create an instance.|
|[getDatabase](#vakata\orm\tablegetdatabase)|Returns the database instance being used.|
|[getDefinition](#vakata\orm\tablegetdefinition)|Get the table definition.|
|[getRelations](#vakata\orm\tablegetrelations)|Get the defined relationships to other tables.|
|[getRelationKeys](#vakata\orm\tablegetrelationkeys)|Get only the names of the defined relationships to other tables.|
|[hasOne](#vakata\orm\tablehasone)|Define a relationship where a remote table has 0 or 1 rows for each row in the current table.|
|[hasMany](#vakata\orm\tablehasmany)|Define a relationship where a remote table has any number of rows for each row in the current table.|
|[belongsTo](#vakata\orm\tablebelongsto)|Define a relationship where a remote table has 0 or 1 rows for each row in the current table.|
|[manyToMany](#vakata\orm\tablemanytomany)|Add a relationship which uses a pivot table (many to many).|
|[select](#vakata\orm\tableselect)|Perform the actual data fetching (after filtering and / or ordering), all params are optional.|
|[where](#vakata\orm\tablewhere)|Filter results using a raw query.|
|[order](#vakata\orm\tableorder)|Order the result set.|
|[filter](#vakata\orm\tablefilter)|Filter the result set by column.|
|[count](#vakata\orm\tablecount)|Get the result set count.|
|[reset](#vakata\orm\tablereset)|Clears all filters, ordering, etc.|
|[toArray](#vakata\orm\tabletoarray)|Get the current result set as an array.|
|[create](#vakata\orm\tablecreate)|Create a new row for the current table (will not be persisted until save() is invoked).|
|[save](#vakata\orm\tablesave)|Persist all changes to database.|
|[delete](#vakata\orm\tabledelete)|Remove the current result set from the database.|

---



### vakata\orm\Table::__construct
Create an instance.  


```php
public function __construct (  
    \DatabaseInterface $database,  
    string $table,  
    \vakata\orm\TableDefinitionInterface|null $definition  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$database` | `\DatabaseInterface` | database instance |
| `$table` | `string` | the table name |
| `$definition` | `\vakata\orm\TableDefinitionInterface`, `null` | the optional table defintion |

---


### vakata\orm\Table::getDatabase
Returns the database instance being used.  


```php
public function getDatabase () : \vakata\database\DatabaseInterface    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `\vakata\database\DatabaseInterface` | the database instance |

---


### vakata\orm\Table::getDefinition
Get the table definition.  


```php
public function getDefinition () : \vakata\orm\TableDefinitionInterface    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `\vakata\orm\TableDefinitionInterface` | the table definition |

---


### vakata\orm\Table::getRelations
Get the defined relationships to other tables.  


```php
public function getRelations () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | the defined relationships |

---


### vakata\orm\Table::getRelationKeys
Get only the names of the defined relationships to other tables.  


```php
public function getRelationKeys () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | an array of strings |

---


### vakata\orm\Table::hasOne
Define a relationship where a remote table has 0 or 1 rows for each row in the current table.  
The remote table generally should have a field (or fields) pointing to the ID of the current table.

```php
public function hasOne (  
    \vakata\orm\TableDefintionInterface|string $toTable,  
    string $name,  
    string|array $toTableColumn  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$toTable` | `\vakata\orm\TableDefintionInterface`, `string` | the table definition or name |
| `$name` | `string` | the name of the relationship (the name is used to access the data later) |
| `$toTableColumn` | `string`, `array` | the column (or columns) in the foreign table |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::hasMany
Define a relationship where a remote table has any number of rows for each row in the current table.  
The remote table generally should have a field (or fields) pointing to the ID of the current table.

```php
public function hasMany (  
    \vakata\orm\TableDefintionInterface|string $toTable,  
    string $name,  
    string|array $toTableColumn  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$toTable` | `\vakata\orm\TableDefintionInterface`, `string` | the table definition or name |
| `$name` | `string` | the name of the relationship (the name is used to access the data later) |
| `$toTableColumn` | `string`, `array` | the column (or columns) in the foreign table |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::belongsTo
Define a relationship where a remote table has 0 or 1 rows for each row in the current table.  
The current table generally should have a field (or fields) pointing to the ID of the foreign table.

```php
public function belongsTo (  
    \vakata\orm\TableDefintionInterface|string $toTable,  
    string $name,  
    string|array $toTableColumn  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$toTable` | `\vakata\orm\TableDefintionInterface`, `string` | the table definition or name |
| `$name` | `string` | the name of the relationship (the name is used to access the data later) |
| `$toTableColumn` | `string`, `array` | the column (or columns) in the current table |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::manyToMany
Add a relationship which uses a pivot table (many to many).  


```php
public function manyToMany (  
    \vakata\orm\TableDefintionInterface|string $toTable,  
    \vakata\orm\TableDefintionInterface|string $pivot,  
    string $name,  
    string|array $toTableColumn,  
    string|array $rlTableColumn  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$toTable` | `\vakata\orm\TableDefintionInterface`, `string` | the table definition or name |
| `$pivot` | `\vakata\orm\TableDefintionInterface`, `string` | the pivot table |
| `$name` | `string` | the name of the relationship (the name is used to access the data later) |
| `$toTableColumn` | `string`, `array` | the column (or columns) in the pivot table connecting to the current table primary key |
| `$rlTableColumn` | `string`, `array` | the column (or columns) in the pivot table connecting to the related table primary key |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::select
Perform the actual data fetching (after filtering and / or ordering), all params are optional.  


```php
public function select (  
    integer $limit,  
    integer $offset,  
    array|null $fields  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$limit` | `integer` | how many rows to fetch |
| `$offset` | `integer` | skip $offset many rows from the beginning |
| `$fields` | `array`, `null` | an array of column names to fetch, if related columns are needed use <relationshipname>.<columnname> |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::where
Filter results using a raw query.  


```php
public function where (  
    string $sql,  
    array $params  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$sql` | `string` | the sql filter query |
| `$params` | `array` | any parameters (if needed for the query) |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::order
Order the result set.  


```php
public function order (  
    string $order,  
    boolean $raw  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$order` | `string` | the order query |
| `$raw` | `boolean` | should the query be used as is, or parsed (defaults to false, which means parsing) |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::filter
Filter the result set by column.  


```php
public function filter (  
    string $column,  
    mixed $value  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$column` | `string` | the column name to filter on (use <relationshipname>.<columnname> to filter on related tables) |
| `$value` | `mixed` | the desired column value (can be a primitive or an array) |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::count
Get the result set count.  


```php
public function count () : int    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `int` | the row count |

---


### vakata\orm\Table::reset
Clears all filters, ordering, etc.  


```php
public function reset () : self    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Table::toArray
Get the current result set as an array.  


```php
public function toArray (  
    boolean $full  
) : array    
```

|  | Type | Description |
|-----|-----|-----|
| `$full` | `boolean` | should relations be fetched as well |
|  |  |  |
| `return` | `array` | the data |

---


### vakata\orm\Table::create
Create a new row for the current table (will not be persisted until save() is invoked).  


```php
public function create (  
    array $data  
) : \vakata\orm\TableRow    
```

|  | Type | Description |
|-----|-----|-----|
| `$data` | `array` | array of values for each column |
|  |  |  |
| `return` | [`\vakata\orm\TableRow`](TableRow.md) | the new row |

---


### vakata\orm\Table::save
Persist all changes to database.  


```php
public function save (  
    array $data,  
    boolean $delete  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$data` | `array` | optionally override all new elements with these values |
| `$delete` | `boolean` | should removed items be deleted from the database (defaults to true) |

---


### vakata\orm\Table::delete
Remove the current result set from the database.  


```php
public function delete ()   
```


---

