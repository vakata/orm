# vakata\orm\Manager
Manager ORM class

## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\manager__construct)|Create an instance|
|[addClass](#vakata\orm\manageraddclass)|Add a class by name and link it to a table|
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
) : $this    
```

|  | Type | Description |
|-----|-----|-----|
| `$class` | `string` | the class to create when reading from the table |
| `$table` | `string` | the table name associated with the class |
|  |  |  |
| `return` | `$this` |  |

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

