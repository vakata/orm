# vakata\orm\UnitOfWork


## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\unitofwork__construct)|Create an instance|
|[register](#vakata\orm\unitofworkregister)|Register an entity in the mapper (will be checked for changes when save is called)|
|[change](#vakata\orm\unitofworkchange)|Mark an entity as changed|
|[add](#vakata\orm\unitofworkadd)|Add a new entity (mark for insertion)|
|[remove](#vakata\orm\unitofworkremove)|Mark an entity for removal|
|[save](#vakata\orm\unitofworksave)|Persist all changes|

---



### vakata\orm\UnitOfWork::__construct
Create an instance  


```php
public function __construct (  
    \DBInterface $db  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$db` | `\DBInterface` | the database object (a transaction will be used when calling save) |

---


### vakata\orm\UnitOfWork::register
Register an entity in the mapper (will be checked for changes when save is called)  


```php
public function register (  
    mixed $entity,  
    \DataMapper $mapper  
) : string    
```

|  | Type | Description |
|-----|-----|-----|
| `$entity` | `mixed` |  |
| `$mapper` | `\DataMapper` | the entity's mapper |
|  |  |  |
| `return` | `string` | the entity's hash |

---


### vakata\orm\UnitOfWork::change
Mark an entity as changed  


```php
public function change (  
    mixed $entity,  
    \DataMapper $mapper  
) : string    
```

|  | Type | Description |
|-----|-----|-----|
| `$entity` | `mixed` |  |
| `$mapper` | `\DataMapper` | the entity's hash |
|  |  |  |
| `return` | `string` | the entity's hash |

---


### vakata\orm\UnitOfWork::add
Add a new entity (mark for insertion)  


```php
public function add (  
    mixed $entity,  
    \DataMapper $mapper  
) : string    
```

|  | Type | Description |
|-----|-----|-----|
| `$entity` | `mixed` |  |
| `$mapper` | `\DataMapper` | the entity's mapper |
|  |  |  |
| `return` | `string` | the entity's hash |

---


### vakata\orm\UnitOfWork::remove
Mark an entity for removal  


```php
public function remove (  
    mixed $entity,  
    \DataMapper $mapper  
) : string    
```

|  | Type | Description |
|-----|-----|-----|
| `$entity` | `mixed` |  |
| `$mapper` | `\DataMapper` | the entity's mapper |
|  |  |  |
| `return` | `string` | the entity's hash |

---


### vakata\orm\UnitOfWork::save
Persist all changes  


```php
public function save () : void    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `void` |  |

---

