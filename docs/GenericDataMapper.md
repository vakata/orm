# vakata\orm\GenericDataMapper
A generic class mapping an instance creation function to a table in the DB.

## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\genericdatamapper__construct)|Create an instance|
|[toArray](#vakata\orm\genericdatamappertoarray)|Convert an entity to an array of fields, optionally including relation fields.|
|[entity](#vakata\orm\genericdatamapperentity)|Get an entity from an array of fields|
|[insert](#vakata\orm\genericdatamapperinsert)|Insert an entity, returning the primary key fields and their value|
|[update](#vakata\orm\genericdatamapperupdate)|Update an entity|
|[delete](#vakata\orm\genericdatamapperdelete)|Delete an entity|

---



### vakata\orm\GenericDataMapper::__construct
Create an instance  


```php
public function __construct (  
    \Manager $manager,  
    \DBInterface $db,  
    string $table,  
    callable $create  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$manager` | `\Manager` | the manager object |
| `$db` | `\DBInterface` | the database access object |
| `$table` | `string` | the table name to query |
| `$create` | `callable` | invoked with an array of fields when a new instance needs to be created |

---


### vakata\orm\GenericDataMapper::toArray
Convert an entity to an array of fields, optionally including relation fields.  


```php
public function toArray (  
    mixed $entity,  
    bool $relations  
) : array    
```

|  | Type | Description |
|-----|-----|-----|
| `$entity` | `mixed` | the entity to convert |
| `$relations` | `bool` | should the 1 end of relations be included, defaults to `true` |
|  |  |  |
| `return` | `array` |  |

---


### vakata\orm\GenericDataMapper::entity
Get an entity from an array of fields  


```php
public function entity (  
    array $row  
) : mixed    
```

|  | Type | Description |
|-----|-----|-----|
| `$row` | `array` |  |
|  |  |  |
| `return` | `mixed` |  |

---


### vakata\orm\GenericDataMapper::insert
Insert an entity, returning the primary key fields and their value  


```php
public function insert (  
    mixed $entity  
) : array    
```

|  | Type | Description |
|-----|-----|-----|
| `$entity` | `mixed` |  |
|  |  |  |
| `return` | `array` | a key value map of the primary key columns |

---


### vakata\orm\GenericDataMapper::update
Update an entity  


```php
public function update (  
    mixed $entity  
) : int    
```

|  | Type | Description |
|-----|-----|-----|
| `$entity` | `mixed` |  |
|  |  |  |
| `return` | `int` | the number of affected rows |

---


### vakata\orm\GenericDataMapper::delete
Delete an entity  


```php
public function delete (  
    mixed $entity  
) : int    
```

|  | Type | Description |
|-----|-----|-----|
| `$entity` | `mixed` |  |
|  |  |  |
| `return` | `int` | the number of deleted rows |

---

