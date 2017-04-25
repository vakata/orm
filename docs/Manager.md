# vakata\orm\Manager
Manager ORM class

## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\manager__construct)|Create an instance|
|[registerMapper](#vakata\orm\managerregistermapper)|Add a mapper for a specific table|
|[registerGenericMapper](#vakata\orm\managerregistergenericmapper)|Add a generic mapper for a table name|
|[registerGenericMapperWithClassName](#vakata\orm\managerregistergenericmapperwithclassname)|Add a generic mapper for a table name using a class name|
|[hasMapper](#vakata\orm\managerhasmapper)|Is a mapper available for a given table name|
|[getMapper](#vakata\orm\managergetmapper)|Get a mapper for a table name, if a mapper is not found a new generic mapper is registered using \StdClass|
|[fromQuery](#vakata\orm\managerfromquery)|Get a repository from a table query|
|[fromTable](#vakata\orm\managerfromtable)|Get a repository for a given table name|

---



### vakata\orm\Manager::__construct
Create an instance  


```php
public function __construct (  
    \DBInterface $db  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$db` | `\DBInterface` | the database schema |

---


### vakata\orm\Manager::registerMapper
Add a mapper for a specific table  


```php
public function registerMapper (  
    string $table,  
    \DataMapper $mapper  
) : $this    
```

|  | Type | Description |
|-----|-----|-----|
| `$table` | `string` | the table name |
| `$mapper` | `\DataMapper` | the mapper instance |
|  |  |  |
| `return` | `$this` |  |

---


### vakata\orm\Manager::registerGenericMapper
Add a generic mapper for a table name  


```php
public function registerGenericMapper (  
    string $table,  
    callable $creator  
) : $this    
```

|  | Type | Description |
|-----|-----|-----|
| `$table` | `string` | the table name |
| `$creator` | `callable` | a callable to invoke when a new instance is needed |
|  |  |  |
| `return` | `$this` |  |

---


### vakata\orm\Manager::registerGenericMapperWithClassName
Add a generic mapper for a table name using a class name  


```php
public function registerGenericMapperWithClassName (  
    string $table,  
    string $class  
) : $this    
```

|  | Type | Description |
|-----|-----|-----|
| `$table` | `string` | the table name |
| `$class` | `string` | the class name to use when creating new instances |
|  |  |  |
| `return` | `$this` |  |

---


### vakata\orm\Manager::hasMapper
Is a mapper available for a given table name  


```php
public function hasMapper (  
    string $table  
) : boolean    
```

|  | Type | Description |
|-----|-----|-----|
| `$table` | `string` | the table name |
|  |  |  |
| `return` | `boolean` |  |

---


### vakata\orm\Manager::getMapper
Get a mapper for a table name, if a mapper is not found a new generic mapper is registered using \StdClass  


```php
public function getMapper (  
    string $table  
) : \DataMapper    
```

|  | Type | Description |
|-----|-----|-----|
| `$table` | `string` |  |
|  |  |  |
| `return` | `\DataMapper` |  |

---


### vakata\orm\Manager::fromQuery
Get a repository from a table query  


```php
public function fromQuery (  
    \TableQuery $query  
) : \Repository    
```

|  | Type | Description |
|-----|-----|-----|
| `$query` | `\TableQuery` |  |
|  |  |  |
| `return` | `\Repository` |  |

---


### vakata\orm\Manager::fromTable
Get a repository for a given table name  


```php
public function fromTable (  
    string $table  
) : \Repository    
```

|  | Type | Description |
|-----|-----|-----|
| `$table` | `string` |  |
|  |  |  |
| `return` | `\Repository` |  |

---

