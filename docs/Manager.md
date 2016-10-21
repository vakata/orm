# vakata\orm\Manager
Manager ORM class

## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\manager__construct)|Create an instance|
|[addClass](#vakata\orm\manageraddclass)|Add a class by name and link it to a table|
|[create](#vakata\orm\managercreate)|Create an instance|
|[entity](#vakata\orm\managerentity)|Create an entity|
|[save](#vakata\orm\managersave)|Persist an instance to DB|
|[delete](#vakata\orm\managerdelete)|Remove an instance from DB|

---



### vakata\orm\Manager::__construct
Create an instance  


```php
public function __construct (  
    \Schema $schema,  
    callable|null $creator  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$schema` | `\Schema` | the database schema |
| `$creator` | `callable`, `null` | optional function used to create all necessary classes |

---


### vakata\orm\Manager::addClass
Add a class by name and link it to a table  


```php
public function addClass (  
    string $class,  
    string $table  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$class` | `string` | the class to create when reading from the table |
| `$table` | `string` | the table name associated with the class |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Manager::create
Create an instance  


```php
public function create (  
    string $search,  
    array $data,  
    \Table|null $definition  
) : mixed    
```

|  | Type | Description |
|-----|-----|-----|
| `$search` | `string` | the type of instance to create (class name, table name, etc) |
| `$data` | `array` | optional array of data to populate with (defaults to an empty array) |
| `$definition` | `\Table`, `null` | optional explicit definition to use |
|  |  |  |
| `return` | `mixed` | the newly created instance |

---


### vakata\orm\Manager::entity
Create an entity  


```php
public function entity (  
    string $class,  
    array $key,  
    array|null $data,  
    \Table|null $definition  
) : mixed    
```

|  | Type | Description |
|-----|-----|-----|
| `$class` | `string` | the class name |
| `$key` | `array` | the ID of the entity |
| `$data` | `array`, `null` | optional data to populate with, if missing it is gathered from DB |
| `$definition` | `\Table`, `null` | optional explicit definition to use when creating the instance |
|  |  |  |
| `return` | `mixed` | the instance |

---


### vakata\orm\Manager::save
Persist an instance to DB  


```php
public function save (  
    mixed $entity  
) : array    
```

|  | Type | Description |
|-----|-----|-----|
| `$entity` | `mixed` | the instance object |
|  |  |  |
| `return` | `array` | the instance's primary key |

---


### vakata\orm\Manager::delete
Remove an instance from DB  


```php
public function delete (  
    mixed $entity  
) : int    
```

|  | Type | Description |
|-----|-----|-----|
| `$entity` | `mixed` | the instance to remove |
|  |  |  |
| `return` | `int` | the deleted rows count |

---

