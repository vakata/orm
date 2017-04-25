# vakata\orm\UnitOfWorkManager
Manager ORM class implementing Unit Of Work, so that all changes are persisted in a single transaction

## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\unitofworkmanager__construct)|Create an instance|
|[fromQuery](#vakata\orm\unitofworkmanagerfromquery)|Get a repository from a table query|
|[save](#vakata\orm\unitofworkmanagersave)|Save all the pending changes|
|[registerMapper](#vakata\orm\unitofworkmanagerregistermapper)|Add a mapper for a specific table|
|[registerGenericMapper](#vakata\orm\unitofworkmanagerregistergenericmapper)|Add a generic mapper for a table name|
|[registerGenericMapperWithClassName](#vakata\orm\unitofworkmanagerregistergenericmapperwithclassname)|Add a generic mapper for a table name using a class name|
|[hasMapper](#vakata\orm\unitofworkmanagerhasmapper)|Is a mapper available for a given table name|
|[getMapper](#vakata\orm\unitofworkmanagergetmapper)|Get a mapper for a table name, if a mapper is not found a new generic mapper is registered using \StdClass|
|[fromTable](#vakata\orm\unitofworkmanagerfromtable)|Get a repository for a given table name|

---



### vakata\orm\UnitOfWorkManager::__construct
Create an instance  


```php
public function __construct (  
    \DBInterface $db,  
    \UnitOfWork $uow  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$db` | `\DBInterface` | the database access object |
| `$uow` | `\UnitOfWork` | the unit of work object |

---


### vakata\orm\UnitOfWorkManager::fromQuery
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


### vakata\orm\UnitOfWorkManager::save
Save all the pending changes  


```php
public function save () : void    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `void` |  |

---


### vakata\orm\UnitOfWorkManager::registerMapper
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


### vakata\orm\UnitOfWorkManager::registerGenericMapper
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


### vakata\orm\UnitOfWorkManager::registerGenericMapperWithClassName
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


### vakata\orm\UnitOfWorkManager::hasMapper
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


### vakata\orm\UnitOfWorkManager::getMapper
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


### vakata\orm\UnitOfWorkManager::fromTable
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

