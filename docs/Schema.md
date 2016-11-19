# vakata\orm\Schema


## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\schema__construct)|Create an instance|
|[addTable](#vakata\orm\schemaaddtable)|Add a table definition to the schema (most of the time you can rely on the autodetected definitions)|
|[addTableByName](#vakata\orm\schemaaddtablebyname)|Autodetect a definition by table name and add it to the schema.|
|[hasTable](#vakata\orm\schemahastable)|Does the schema have a given table.|
|[getTable](#vakata\orm\schemagettable)|Get an existing definition.|
|[addAllTables](#vakata\orm\schemaaddalltables)|Add all tables from database.|
|[toArray](#vakata\orm\schematoarray)|Get the full schema as an array that you can serialize and store|
|[fromArray](#vakata\orm\schemafromarray)|Load the schema data from a schema definition array (obtained from toArray)|
|[query](#vakata\orm\schemaquery)|Get a query object for a table|

---



### vakata\orm\Schema::__construct
Create an instance  


```php
public function __construct (  
    \DatabaseInterface $this->db,  
    callable|null $creator  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$this->db` | `\DatabaseInterface` | the database connection |
| `$creator` | `callable`, `null` | optional function used to create all necessary classes |

---


### vakata\orm\Schema::addTable
Add a table definition to the schema (most of the time you can rely on the autodetected definitions)  


```php
public function addTable (  
    \Table $definition  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$definition` | `\Table` | the definition |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Schema::addTableByName
Autodetect a definition by table name and add it to the schema.  


```php
public function addTableByName (  
    string $table,  
    bool|boolean $detectRelations  
) : \the    
```

|  | Type | Description |
|-----|-----|-----|
| `$table` | `string` | the table to analyze |
| `$detectRelations` | `bool`, `boolean` | should relations be extracted - defaults to `true` |
|  |  |  |
| `return` | `\the` | newly added definition |

---


### vakata\orm\Schema::hasTable
Does the schema have a given table.  


```php
public function hasTable (  
    string $search  
) : bool    
```

|  | Type | Description |
|-----|-----|-----|
| `$search` | `string` | the table name |
|  |  |  |
| `return` | `bool` | does the schema contain this table |

---


### vakata\orm\Schema::getTable
Get an existing definition.  


```php
public function getTable (  
    string $search,  
    string $autodetect  
) : \Table    
```

|  | Type | Description |
|-----|-----|-----|
| `$search` | `string` | the table name |
| `$autodetect` | `string` | load the definition from the database if not present - defaults to `true` |
|  |  |  |
| `return` | `\Table` | the table definition |

---


### vakata\orm\Schema::addAllTables
Add all tables from database.  


```php
public function addAllTables () : self    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Schema::toArray
Get the full schema as an array that you can serialize and store  


```php
public function toArray () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` |  |

---


### vakata\orm\Schema::fromArray
Load the schema data from a schema definition array (obtained from toArray)  


```php
public function fromArray (  
    array $data  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$data` | `array` | the schema definition |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Schema::query
Get a query object for a table  


```php
public function query (  
    \Table|string $table  
) : \vakata\orm\Query    
```

|  | Type | Description |
|-----|-----|-----|
| `$table` | `\Table`, `string` | the table definition or name |
|  |  |  |
| `return` | [`\vakata\orm\Query`](Query.md) |  |

---

