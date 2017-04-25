# vakata\orm\DataMapper


## Methods

| Name | Description |
|------|-------------|
|[entity](#vakata\orm\datamapperentity)|Get an entity from an array of fields|
|[insert](#vakata\orm\datamapperinsert)|Insert an entity, returning the primary key fields and their value|
|[update](#vakata\orm\datamapperupdate)|Update an entity|
|[delete](#vakata\orm\datamapperdelete)|Delete an entity|
|[toArray](#vakata\orm\datamappertoarray)|Convert an entity to an array of fields, optionally including relation fields.|

---



### vakata\orm\DataMapper::entity
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


### vakata\orm\DataMapper::insert
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


### vakata\orm\DataMapper::update
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


### vakata\orm\DataMapper::delete
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


### vakata\orm\DataMapper::toArray
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

